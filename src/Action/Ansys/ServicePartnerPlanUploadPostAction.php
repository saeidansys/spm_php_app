<?php

namespace App\Action\Ansys;

use App\Action\BaseAction;
use App\Factory\LoggerFactory;
use App\Service\Ansys\AnsysService;
use App\Service\MailService;
use Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Views\Twig;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

final class ServicePartnerPlanUploadPostAction extends BaseAction
{

    protected AnsysService $service;

    public function __construct(
        LoggerFactory $loggerFactory,
        Session $session,
        MailService $mailService,
        Twig $twig,
        AnsysService $service
    ){
        parent::__construct($loggerFactory, $session, $mailService, $twig);
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        try {
            $data = [];
            $uploadNotifications = [];
            $newErrors = [];
            $spId = $this->session->get('spid');
            if ($spId === null) {
                return $response->withStatus(404);
            }
            $directory = sprintf('%s/../tmp/uploads', getcwd());
            $uploadedFiles = $request->getUploadedFiles();
            // handle single input with single file upload
            $uploadedFile = $uploadedFiles['excelfile'];
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $filename = $this->moveUploadedFile($directory, $uploadedFile);
                $reader = new Xlsx();
                $spreadsheet = $reader->load($filename);
                $rows = $spreadsheet->getActiveSheet()
                    ->toArray(null, false, false, false, true);
                $rowCount = 0;
                foreach ($rows as $row) {
                    $fileErrors = [];
                    $rowCount++;
                    if ($rowCount == 1) {
                        if (trim($row[0]) != 'Consumed Update Date') {
                            $fileErrors[] = 'Column A must be "Consumed Update Date"';
                        }
                        if (trim($row[1]) != 'SP PO N°') {
                            $fileErrors[] = 'Column B must be "SP PO N°"';
                        }
                        if (trim($row[2]) != 'Project ID') {
                            $fileErrors[] = 'Column C must be "Project ID"';
                        }
                        if (trim($row[3]) != 'Measurement Reporting Unit ( Hr, Day, Money)') {
                            $fileErrors[] = 'Column D must be "Measurement Reporting Unit ( Hr, Day, Money)"';
                        }
                        if (trim($row[4]) != 'Baseline') {
                            $fileErrors[] = 'Column E must be "Baseline"';
                        }
                        if (trim($row[5]) != 'Consumed') {
                            $fileErrors[] = 'Column F must be "Consumed"';
                        }
                        if (trim($row[6]) != 'SP Rate') {
                            $fileErrors[] = 'Column G must be "SP Rate"';
                        }
                        if (trim($row[7]) != 'SP Currency') {
                            $fileErrors[] = 'Column H must be "SP Currency"';
                        }
                        if (trim($row[14]) != 'SP Resource Type') {
                            $fileErrors[] = 'Column O must be "SP Resource Type"';
                        }
                        if (trim($row[15]) != 'Project Task/WP Name') {
                            $fileErrors[] = 'Column P must be "Project Task/WP Name"';
                        }
                        if (trim($row[16]) != 'Project Activity Name') {
                            $fileErrors[] = 'Column Q must be "Project Activity Name"';
                        }
                        if (trim($row[17]) != 'Executive Summary') {
                            $fileErrors[] = 'Column R must be "Executive Summary"';
                        }
                        if (trim($row[18]) != 'SP Unique Identifier') {
                            $fileErrors[] = 'Column S must be "SP Unique Identifier"';
                        }
                    }
                    if ($rowCount == 2) {
                        if (trim($row[8]) != 'Month') {
                            $fileErrors[] = 'Column I must be "Project Start Date  ( MM)"';
                        }
                        if (trim($row[9]) != 'Day') {
                            $fileErrors[] = 'Column J must be "Project Start Date  ( DD)"';
                        }
                        if (trim($row[10]) != 'Year') {
                            $fileErrors[] = 'Column K must be "Project Start Date  ( YYYY)"';
                        }
                        if (trim($row[11]) != 'Month') {
                            $fileErrors[] = 'Column L must be "Project End date ( MM)"';
                        }
                        if (trim($row[12]) != 'Day') {
                            $fileErrors[] = 'Column M must be "Project End date ( DD)"';
                        }
                        if (trim($row[13]) != 'Year') {
                            $fileErrors[] = 'Column N must be "Project End date ( YYYY)"';
                        }
                    }
                    if (!empty($fileErrors)) {
                        return $this->twig->render($response, 'ansys/sp-pp-upload-file-errors.twig', [
                            'spid64' => base64_encode($spId),
                            'newerrors' => $fileErrors
                        ]);
                    }
                    if ($rowCount < 3) {
                        continue;
                    }
                    $data[] = [
                        'consumed_update_date' => $row[0],
                        'sppon' => $row[1],
                        'project_id' => $row[2],
                        'project_type' => $row[3],
                        'baseline' => $row[4],
                        'consumed_actual' => $row[5],
                        'sp_rate' => $row[6],
                        'sp_currency' => $row[7],
                        'psd_month' => $row[8],
                        'psd_day' => $row[9],
                        'psd_year' => $row[10],
                        'ped_month' => $row[11],
                        'ped_day' => $row[12],
                        'ped_year' => $row[13],
                        'sp_res_type' => $row[14],
                        'project_task' => $row[15],
                        'project_activity_name' => $row[16],
                        'executive_summary' => $row[17],
                        'sp_unique_id' => $row[18],
                        'row_in_file' => $rowCount
                    ];
                }
                $this->session->set('uploadeddata', $data);
                $uploadResultArray = $this->service->processUploadedSpPpData($data, $spId);
                $uploadNotifications = $uploadResultArray['notifications'];
                $newErrors = $uploadResultArray['newerrors'];
            }
            return $this->twig->render($response, 'ansys/sp-pp-upload-result.twig', [
                'spid64' => base64_encode($spId),
                'notifications' => $uploadNotifications,
                'newerrors' => $newErrors,
                'uploadeddata' => $data
            ]);
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'ANSYS ServicePartnerPlanUploadPostAction');
        }
    }

    /**
     * @throws Exception
     */
    private function moveUploadedFile(string $directory, UploadedFileInterface $uploadedFile): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $fulluploadname = sprintf('%s%s%s', $directory, DIRECTORY_SEPARATOR, $filename);
        $uploadedFile->moveTo($fulluploadname);
        return $fulluploadname;
    }

}
