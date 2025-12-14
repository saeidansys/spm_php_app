<?php

namespace App\Service\Ansys;

use App\Classes\UtilityProvider;
use App\Service\BaseCustomerService;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class AnsysSpImportService extends BaseCustomerService
{

    protected array $importStructure = [];

    public function processImportFiles(): bool
    {

        $zipFile = sprintf('%s/data/spppupload/final_import_files.zip', getcwd());
        $zip = new ZipArchive();

        if ($zip->open($zipFile) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $this->importStructure = [];
                $this->errors = [];
                $fileName = $zip->getNameIndex($i);
                $this->logger->info(sprintf('processing file : %s', $fileName));
                $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($fileName);
                file_put_contents($tempPath, $zip->getFromIndex($i));
                $spreadsheet = IOFactory::load($tempPath);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false, true);
                $rowCount = 0;
                foreach ($rows as $row) {
                    $rowCount++;
                    if ($rowCount == 1) {
                        $projectIdLabel = $row[4];
                        if (strtolower(trim($projectIdLabel)) != 'project id') {
                            $this->addError(sprintf('File %s: the column "project id" must be in column "E" of the Excel file - skipping file', $fileName), 'danger');
                            $this->logger->error(sprintf('File %s: the column "project id" must be in column "E" of the Excel file - skipping file', $fileName));
                            break;
                        }
                        continue;
                    }
                    $subitemName = $row[1];
                    if ($subitemName == '') {
                        continue;
                    }
                    $ansysCustomer = $row[2];
                    $poNumber = $row[3];
                    $projectId = $row[4];
                    $snapshotDateRaw = $row[5];
                    if ($snapshotDateRaw != '') {
                        $snapshotDate = DateTime::createFromFormat('m/d/Y', $snapshotDateRaw);
                    }
                    else {
                        $snapshotDate = null;
                    }
                    $projectType = $row[6];
                    $currency = $row[7];
                    $baseline = $row[8];
                    $planned = $row[9];
                    $consumed = $row[10];
                    $rate = $row[11];
                    $resourceType = $row[12];
                    $resourceGroup = $row[13];
                    $servicePartner = $row[14];
                    $resSource = $row[15];
                    // check if we have a project id
                    if ($projectId == '') {
                        $this->addError(sprintf('Row %s: does not have a project id, unable to process rows without project id', $rowCount), 'danger');
                        $this->logger->error(sprintf('Row %s: does not have a project id, unable to process rows without project id', $rowCount));
                        continue;
                    }
                    // now that we have all the data, we can start building our import structure
                    if (!array_key_exists($projectId, $this->importStructure)) {
                        $mondayProject = $this->getItemByColumnValueV2(AnsysService::BOARD_ID_PROJECTS, 'text', $projectId, true);
                        if (!isset($mondayProject[0])) {
                            $this->addError(sprintf('Row %s: project id %s could not be found in Monday, unable to process rows that are not in Monday', $rowCount, $projectId), 'danger');
                            $this->logger->error(sprintf('Row %s: project id %s could not be found in Monday, unable to process rows that are not in Monday', $rowCount, $projectId));
                            continue;
                        }
                        $this->importStructure[$projectId] = [
                            'resources' => [],
                            'monday' => $mondayProject[0],
                        ];
                    }
                    if ($resSource == '') {
                        $this->addError(sprintf('Row %s: does not have a source, unable to process rows without source', $rowCount), 'danger');
                        $this->logger->error(sprintf('Row %s: does not have a source, unable to process rows without source', $rowCount));
                        continue;
                    }
                    // check if we already have this resource
                    if (!array_key_exists($resSource, $this->importStructure[$projectId]['resources'])) {
                        $this->importStructure[$projectId]['resources'][$resSource] = [
                            'snapshots' => [],
                            'total_baseline' => 0,
                            'total_planned' => 0,
                            'total_consumed' => 0,
                            'running_consumed' => 0,
                            'latest_cud' => $snapshotDate,
                            'sppon' => $poNumber,
                            'project_id' => $projectId,
                            'measurement_reporting_unit' => $projectType,
                            'sp_rate' => (float)$rate,
                            'sp_currency' => $currency,
                            'project_start' => $snapshotDate,
                            'project_end' => $snapshotDate,
                            'sp_resource_type' => $resourceType,
                            'project_task' => null,
                            'project_activity' => null,
                            'executive_summary' => null,
                            'sp_name' => $servicePartner,
                            'ansys_customer_name' => $ansysCustomer,
                            'project_name' => $this->importStructure[$projectId]['monday'],
                        ];
                    }
                    $this->importStructure[$projectId]['resources'][$resSource]['snapshots'][] = $row;
                    $this->importStructure[$projectId]['resources'][$resSource]['total_baseline'] = $this->importStructure[$projectId]['resources'][$resSource]['total_baseline'] + (float)$baseline;
                    $this->importStructure[$projectId]['resources'][$resSource]['total_planned'] = $this->importStructure[$projectId]['resources'][$resSource]['total_planned'] + (float)$planned;
                    $this->importStructure[$projectId]['resources'][$resSource]['total_consumed'] = $this->importStructure[$projectId]['resources'][$resSource]['total_consumed'] + (float)$consumed;
                    // check if we need to set latest cud to this row's snapdate if it is more recent than current
                    if ($snapshotDate instanceof DateTime && $snapshotDate > $this->importStructure[$projectId]['resources'][$resSource]['latest_cud']) {
                        $this->importStructure[$projectId]['resources'][$resSource]['latest_cud'] = $snapshotDate;
                    }
                    if ($snapshotDate instanceof DateTime && $snapshotDate > $this->importStructure[$projectId]['resources'][$resSource]['project_end']) {
                        $this->importStructure[$projectId]['resources'][$resSource]['project_end'] = $snapshotDate;
                    }
                    if ($snapshotDate instanceof DateTime && $snapshotDate < $this->importStructure[$projectId]['resources'][$resSource]['project_start']) {
                        $this->importStructure[$projectId]['resources'][$resSource]['project_start'] = $snapshotDate;
                    }
                }
                unlink($tempPath);
                foreach ($this->importStructure as $currentProjectId => $projectData) {
                    $this->logger->info(sprintf('Starting with project: %s', $currentProjectId));
                    $mondayObject = $projectData['monday'];
                    $spPpBoardId = UtilityProvider::getColumnValueFromItem($mondayObject, 'text_mkp79mp3', true);
                    $spSnapshotBoardId = UtilityProvider::getColumnValueFromItem($mondayObject, 'text_mkpefcyf', true);
                    if ($spPpBoardId == '') {
                        $spPpBoardId = $this->createSpPpBoard($mondayObject);
                        $this->changeMultipleColumnValues(AnsysService::BOARD_ID_PROJECTS, $mondayObject['id'], ['text_mkp79mp3' => $spPpBoardId]);
                    }
                    if ($spSnapshotBoardId == '') {
                        $spSnapshotBoardId = $this->createSpSnapshotBoard($mondayObject);
                        $this->changeMultipleColumnValues(AnsysService::BOARD_ID_PROJECTS, $mondayObject['id'], ['text_mkpefcyf' => $spSnapshotBoardId]);
                    }
                    foreach ($projectData['resources'] as $resourceId => $resourceData) {
                        $existingResource = null;
                        $existingResources = $this->getItemByColumnValueV2($spPpBoardId, 'text_mkq39mrg', $resourceId, true);
                        if (isset($existingResources[0])) {
                            $existingResource = $existingResources[0];
                        }
                        $columnValues = [
                            'text_mkp77v4' => (string)$currentProjectId,
                            'text_mkp7vn15' => $resourceData['sp_name'],
                            'text_mkp7t6f4' => $resourceData['sppon'],
                            'text_mkp7zykp' => $resourceData['ansys_customer_name'],
                            'text_mkp79jwd' => $resourceData['measurement_reporting_unit'],
                            'numeric_mkp7kexx' => $resourceData['total_baseline'],
                            'numeric_mkp7wmgz' => $resourceData['total_consumed'],
                            'numeric_mkpcbzyt' => $resourceData['total_planned'],
                            'numeric_mkp7b54w' => $resourceData['sp_rate'],
                            'text_mkp7krca' => $resourceData['sp_currency'],
                            'text_mkp75v7x' => $resourceData['sp_resource_type'],
                            'text_mkq39mrg' => $resourceId,
                            'text_mkq0d46m' => $mondayObject['name']
                        ];
                        if ($resourceData['latest_cud'] instanceof DateTime) {
                            $columnValues['date4'] = ['date' => $resourceData['latest_cud']->format('Y-m-d')];
                        }
                        if ($resourceData['project_start'] instanceof DateTime) {
                            $columnValues['date_mkp7h80n'] = ['date' => $resourceData['project_start']->format('Y-m-d')];
                        }
                        if ($resourceData['project_end'] instanceof DateTime) {
                            $columnValues['date_mkp75ed1'] = ['date' => $resourceData['project_end']->format('Y-m-d')];
                        }
                        if ($existingResource !== null) {
                            // update pp item
                            $newSpPpItemId = $existingResource['id'];
                            $this->changeMultipleColumnValues($spPpBoardId, $existingResource['id'], $columnValues, true);
                        }
                        else {
                            // create new pp item
                            $newSpPpItemId = $this->createSpPpItem($spPpBoardId, $mondayObject['name'], $columnValues);
                            $this->changeMultipleColumnValues($spPpBoardId, $newSpPpItemId, ['text_mkp758ax' => $newSpPpItemId]);
                        }
                        foreach ($resourceData['snapshots'] as $snapshotRow) {
                            $testSource = $snapshotRow[15];
                            $testSnapshotDateRaw = $snapshotRow[5];
                            $testSnapshotDate = DateTime::createFromFormat('m/d/Y', $testSnapshotDateRaw);
                            if (!$testSnapshotDate instanceof DateTime) {
                                $this->logger->error(sprintf('Row %s: row has no date, unable to create snapshots for a row without date - skipping row', $rowCount));
                                continue;
                            }
                            $resourceData['running_consumed'] = $resourceData['running_consumed'] + (float)$snapshotRow[10];
                            $possibleSnapshots = $this->getItemByColumnValueV2($spSnapshotBoardId, 'text_mkq36p6k', $testSource, true);
                            $targetResource = null;
                            foreach ($possibleSnapshots as $possSnap) {
                                $snapshotDateJson = json_decode(UtilityProvider::getColumnValueFromItem($possSnap, 'date4'), true);
                                $snapshotDateJsonDate = DateTime::createFromFormat('Y-m-d', $snapshotDateJson['date']);
                                if ($snapshotDateJsonDate->format('Y-m') == $testSnapshotDate->format('Y-m')) {
                                    $targetResource = $possSnap;
                                    break;
                                }
                            }
                            if ($targetResource !== null) {
                                $columnValues = [
                                    'text_mkpjarh6' => $resourceData['ansys_customer_name'],
                                    'text_mkpjhbwn' => $snapshotRow[3],
                                    'date4' => $testSnapshotDate->format('Y-m-d'),
                                    'text_mkpje09w' => $snapshotRow[6],
                                    'text_mkpjmnse' => $snapshotRow[7],
                                    'numeric_mkpedsg1' => $resourceData['total_baseline'],
                                    'numeric_mkpeqy39' => $resourceData['total_planned'],
                                    'numeric_mkpenk2c' => $resourceData['running_consumed'],
                                    'numeric_mkpehda6' => (float)$snapshotRow[11],
                                    'text_mkpecwmx' => $snapshotRow[12],
                                    'text_mkpjb40f' => $snapshotRow[14],
                                    'text_mkpjdstr' => 'Service Partner',
                                    'date_mkpeb8ay' => $testSnapshotDate->format('Y-m-d'),
                                ];
                                $this->changeMultipleColumnValues($spSnapshotBoardId, $targetResource['id'], $columnValues, true);
                            }
                            else {
                                $columnValues = [
                                    'text_mkpjarh6' => $resourceData['ansys_customer_name'],
                                    'text_mkpjhbwn' => $snapshotRow[3],
                                    'date4' => $testSnapshotDate->format('Y-m-d'),
                                    'text_mkpje09w' => $snapshotRow[6],
                                    'text_mkpjmnse' => $snapshotRow[7],
                                    'numeric_mkpedsg1' => $resourceData['total_baseline'],
                                    'numeric_mkpeqy39' => $resourceData['total_planned'],
                                    'numeric_mkpenk2c' => $resourceData['running_consumed'],
                                    'numeric_mkpehda6' => (float)$snapshotRow[11],
                                    'text_mkpecwmx' => $snapshotRow[12],
                                    'text_mkpjb40f' => $snapshotRow[14],
                                    'text_mkq36p6k' => $snapshotRow[15],
                                    'text_mkpjdstr' => 'Service Partner',
                                    'date_mkpeb8ay' => $testSnapshotDate->format('Y-m-d'),
                                    'text_mkperesz' => (string)$newSpPpItemId
                                ];
                                $snapshotName = sprintf('Snapshot %s', $testSnapshotDate->format('Y-m-d'));
                                $this->createSpSnapshotItem($spSnapshotBoardId, $snapshotName, $columnValues);
                            }
                        }
                    }
                }
            }
        }
        $zip->close();
        return true;
    }

}
