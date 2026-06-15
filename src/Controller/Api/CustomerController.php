<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\Customer;
use Symfony\Component\HttpFoundation\JsonResponse; //sends  json request back to the react 
use Symfony\Component\HttpFoundation\Request; // import files 
use Symfony\Component\HttpFoundation\StreamedResponse;   //large file downloads 
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Route('/api')]
class CustomerController extends AbstractController
{
    // private function generateCdpId(): string
    // {
    //     return 'CDP-' . strtoupper(substr(md5(uniqid()), 0, 7));
    // }

    private function getOrCreateFolder(): int
    {
        $folder = \Pimcore\Model\DataObject::getByPath('/Customers360');
        if (!$folder) {
            $folder = new \Pimcore\Model\DataObject\Folder();
            $folder->setKey('Customers360');
            $folder->setParentId(1);
            $folder->save();
        }
        return $folder->getId();
    }

    private function format(Customer $customer): array
    {
        return [
            'id'            => $customer->getId(),
            'cdpId'         => $customer->getCdpId() ?? '',
            'customerName'  => $customer->getCustomerName() ?? '',
            'customerType'  => $customer->getCustomerType() ?? '',
            'email'         => $customer->getEmail() ?? '',
            'phone'         => $customer->getPhone() ?? '',
            'primarySource' => $customer->getPrimarySource() ?? '',
            'status'        => $customer->getStatus() ?? '',
            'lifetimeValue' => (float)($customer->getLifetimeValue() ?? 0),
            'lastUpdated'   => $customer->getLastUpdated() ?? date('Y-m-d'),
            'createdAt'     => date('Y-m-d', $customer->getCreationDate()),
        ];
    }

    #[Route('/customer360', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $list = new Customer\Listing();
        $list->setUnpublished(true);
        $q = $request->query->get('q', '');
        if (!empty($q)) {
            $list->setCondition(
                "customerName LIKE ? OR email LIKE ? OR phone LIKE ? OR cdpId LIKE ?",
                ["%$q%", "%$q%", "%$q%", "%$q%"]
            );
        }
        return new JsonResponse(array_map([$this, 'format'], $list->load()));
    }

    #[Route('/customer360/stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $list = new Customer\Listing();
        $list->setpublished(true);
        $all  = $list->load();

        $startOfMonth = (new \DateTime('first day of this month'))->setTime(0, 0);
        $oneWeekAgo   = (new \DateTime())->modify('-7 days');
        $total        = count($all);
        $activeMonth  = 0;
        $newWeek      = 0;
        $totalLTV     = 0;

        foreach ($all as $c) {
            if ($c->getStatus() === 'Active') {
                $lu = $c->getLastUpdated();
                if ($lu && new \DateTime($lu) >= $startOfMonth) $activeMonth++;
            }
            $created = new \DateTime('@' . $c->getCreationDate());
            if ($created >= $oneWeekAgo) $newWeek++;
            $totalLTV += (float)($c->getLifetimeValue() ?? 0);
        }

        return new JsonResponse([
            'total'            => $total,
            'activeThisMonth'  => $activeMonth,
            'newThisWeek'      => $newWeek,
            'avgLifetimeValue' => $total > 0 ? round($totalLTV / $total, 2) : 0,
        ]);
    }

    #[Route('/customer360/export', methods: ['GET'])]
    public function export(): StreamedResponse
    {
        $list = new Customer\Listing();
        $list->setUnpublished(true);
        $customers = $list->load();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers');

        $headers = ['CDP ID', 'Customer Name', 'Customer Type', 'Email', 'Phone', 'Primary Source', 'Status', 'Lifetime Value', 'Last Updated'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->getCell($col . '1')->setValue($header);
            $col++;
        }

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0EA5E9']],
        ]);

        foreach ($customers as $i => $c) {
            $row = $i + 2;
            $sheet->getCell('A' . $row)->setValue($c->getCdpId() ?? '');
            $sheet->getCell('B' . $row)->setValue($c->getCustomerName() ?? '');
            $sheet->getCell('C' . $row)->setValue($c->getCustomerType() ?? '');
            $sheet->getCell('D' . $row)->setValue($c->getEmail() ?? '');
            $sheet->getCell('E' . $row)->setValue($c->getPhone() ?? '');
            $sheet->getCell('F' . $row)->setValue($c->getPrimarySource() ?? '');
            $sheet->getCell('G' . $row)->setValue($c->getStatus() ?? '');
            $sheet->getCell('H' . $row)->setValue((float)($c->getLifetimeValue() ?? 0));
            $sheet->getCell('I' . $row)->setValue($c->getLastUpdated() ?? '');
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($spreadsheet);
        $filename = 'customers_' . date('Y-m-d') . '.xlsx';

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');

        return $response;
    }

#[Route('/customer360/import', methods: ['POST', 'OPTIONS'])]
public function import(Request $request): JsonResponse
{
    if ($request->getMethod() === 'OPTIONS') {
        $response = new JsonResponse(null, 204);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        return $response;
    }

    ini_set('memory_limit', '512M');
    set_time_limit(600);

    $file = $request->files->get('file');
    if (!$file) {
        return new JsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    try {
        // ── STEP 1: Read ALL rows into a plain array, then free spreadsheet memory ──
        $reader = IOFactory::createReaderForFile($file->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();

        $rows = [];
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            $data = [];
            foreach ($cells as $cell) {
                $data[] = $cell->getValue();
            }
            if (!empty(array_filter($data))) {
                $rows[] = [
                    'rowNum' => $row->getRowIndex(),
                    'data'   => $data,
                ];
            }
        }

        // Free spreadsheet from memory before saving objects
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet, $reader);
        gc_collect_cycles();

        $parentId = $this->getOrCreateFolder();
        $log      = [];
        $success  = 0;
        $failed   = 0;

        $log[] = "=== Customer Import Log ===";
        $log[] = "Date  : " . date('Y-m-d H:i:s');
        $log[] = "File  : " . $file->getClientOriginalName();
        $log[] = "Rows  : " . count($rows);
        $log[] = str_repeat('-', 55);

        // ── STEP 2: Iterate every row one by one and save as separate Pimcore object ──
        foreach ($rows as $rowInfo) {
            $rowNum = $rowInfo['rowNum'];
            $data   = $rowInfo['data'];
            $name   = trim($data[1] ?? '');

            try {
                $c = new Customer();

                // Guaranteed-unique key: rowNum + high-entropy uniqid
                $uniqueKey = $rowNum . '-' . uniqid('', true);
                $c->setKey(\Pimcore\Model\Element\Service::getValidKey(
                    ($name ?: 'customer') . '-' . $uniqueKey,
                    'object'
                ));

                $c->setParentId($parentId);
                $c->setCdpId(!empty($data[0]) ? trim($data[0]) : $this->generateCdpId());
                $c->setCustomerName($name);
                $c->setCustomerType(trim($data[2] ?? ''));
                $c->setEmail(trim($data[3] ?? ''));
                $c->setPhone(trim($data[4] ?? ''));
                $c->setPrimarySource(trim($data[5] ?? ''));
                $c->setStatus(trim($data[6] ?? 'Active'));
                $c->setLifetimeValue((float)($data[7] ?? 0));
                $c->setLastUpdated(!empty($data[8]) ? trim($data[8]) : date('Y-m-d'));
                $c->setPublished(true);
                $c->setOmitMandatoryCheck(true);
                $c->save();

                $savedId = $c->getId();
                unset($c);
                gc_collect_cycles();

                $log[] = "[ROW $rowNum] SUCCESS — '$name' (ID: $savedId)";
                $success++;

            } catch (\Throwable $e) {
                $log[] = "[ROW $rowNum] FAILED  — '$name' — " . $e->getMessage();
                $failed++;
            }
        }

        $log[] = str_repeat('-', 55);
        $log[] = "Total: " . ($success + $failed) . " | Imported: $success | Failed: $failed";

        // ── STEP 3: Write log file ──
        $logDir = PIMCORE_PROJECT_ROOT . '/var/log';
        if (!is_dir($logDir)) mkdir($logDir, 0775, true);
        $logName = 'customer_import_' . date('Y-m-d_H-i-s') . '.log';
        file_put_contents($logDir . '/' . $logName, implode(PHP_EOL, $log));

        // ── STEP 4: Sync Pimcore admin (OpenSearch) — synchronous so admin shows all objects immediately ──
        $consolePath = PIMCORE_PROJECT_ROOT . '/bin/console';
        exec("php {$consolePath} messenger:consume pimcore_generic_data_index_queue --limit=2000 --time-limit=60 2>&1");

        return new JsonResponse([
            'success'  => true,
            'imported' => $success,
            'failed'   => $failed,
            'logFile'  => $logName,
            'log'      => $log,
        ]);

    } catch (\Throwable $e) {
        return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}


    // #[Route('/customer360/add', methods: ['POST'])]
    // public function add(Request $request): JsonResponse
    // {
    //     try {
    //         $data = json_decode($request->getContent(), true);
    //         if (!$data) return new JsonResponse(['success' => false, 'message' => 'Invalid JSON'], 400);

    //         $c = new Customer();
    //         $c->setKey(\Pimcore\Model\Element\Service::getValidKey(($data['customerName'] ?? 'customer') . '-' . time(), 'object'));
    //         $c->setParentId($this->getOrCreateFolder());
    //         $c->setCdpId($data['cdpId'] ?? $this->generateCdpId());
    //         $c->setCustomerName($data['customerName'] ?? '');
    //         $c->setCustomerType($data['customerType'] ?? '');
    //         $c->setEmail($data['email'] ?? '');
    //         $c->setPhone($data['phone'] ?? '');
    //         $c->setPrimarySource($data['primarySource'] ?? '');
    //         $c->setStatus($data['status'] ?? 'Active');
    //         $c->setLifetimeValue((float)($data['lifetimeValue'] ?? 0));
    //         $c->setLastUpdated($data['lastUpdated'] ?? date('Y-m-d'));
    //         $c->setPublished(true);
    //         $c->setOmitMandatoryCheck(true);
    //         $c->save();

    //         return new JsonResponse(['success' => true, 'id' => $c->getId()], 201);
    //     } catch (\Throwable $e) {
    //         return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    //     }
    // }

    #[Route('/customer360/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $c = Customer::getById($id);
        if (!$c) return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);
        $data = json_decode($request->getContent(), true);
        if (isset($data['customerName']))  $c->setCustomerName($data['customerName']);
        if (isset($data['customerType']))  $c->setCustomerType($data['customerType']);
        if (isset($data['email']))         $c->setEmail($data['email']);
        if (isset($data['phone']))         $c->setPhone($data['phone']);
        if (isset($data['primarySource'])) $c->setPrimarySource($data['primarySource']);
        if (isset($data['status']))        $c->setStatus($data['status']);
        if (isset($data['lifetimeValue'])) $c->setLifetimeValue((float)$data['lifetimeValue']);
        if (isset($data['lastUpdated']))   $c->setLastUpdated($data['lastUpdated']);
        $c->setOmitMandatoryCheck(true);
        $c->save();
        return new JsonResponse(['success' => true, 'data' => $this->format($c)]);
    }

    #[Route('/customer360/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $c = Customer::getById($id);
        if (!$c) return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);
        $c->delete();
        return new JsonResponse(['success' => true, 'message' => 'Deleted']);
    }
}
