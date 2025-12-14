<?php

namespace App\Service\Ansys;

use App\Classes\UtilityProvider;
use App\Entity\AnsysSpItem;
use App\Entity\AnsysSpItemSnapshot;
use App\Factory\LoggerFactory;
use App\Factory\SiteSettingsFactory;
use App\Service\BaseCustomerService;
use App\Service\DatabaseService;
use App\Service\GqlClient;
use DateTime;
use Exception;
use GraphQL\Results;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\HttpFoundation\Session\Session;

class AnsysService extends BaseCustomerService
{

    const TOKEN_JSON_SP_PP_ITEMS = 8849637288;

    const TOKEN_JSON_SP_PP_ITEM_HISTORY = 8849640660;

    const BOARD_ID_PROJECTS = 5680079537;

    const BOARD_ID_SERVICE_PARTNERS = 8624698299;

    const BOARD_ID_DB_UPDATES = 5826822953;

    const BOARD_ID_CURRENCY_CONFIGURATION = 8828087407;

    protected DatabaseService $databaseService;

    protected Session $session;

    protected array $spUploadErrorRows;

    protected array $availableCurrencies;

    public function __construct(
        LoggerFactory $loggerFactory,
        GqlClient $gqlClient,
        SiteSettingsFactory $siteSettingsFactory,
        DatabaseService $databaseService,
        Session $session
    ){
        parent::__construct($loggerFactory, $gqlClient, $siteSettingsFactory);
        $this->databaseService = $databaseService;
        $this->session = $session;
        $this->spUploadErrorRows = [];
        $this->availableCurrencies = [];
    }

    public function exportAllSpItemsEntries(): array
    {
        $result = [];
        $repo = $this->databaseService->getRepo(AnsysSpItem::class);
        $dbItems = $repo->getAllAsArray();
        foreach ($dbItems as $dbItem) {
            unset($dbItem['id']);
            $result[] = $dbItem;
        }
        return $result;
    }

    public function exportAllSpItemHistoryEntries(): array
    {
        $result = [];
        $repo = $this->databaseService->getRepo(AnsysSpItemSnapshot::class);
        $dbItems = $repo->getAllAsArray();
        foreach ($dbItems as $dbItem) {
            unset($dbItem['id']);
            $result[] = $dbItem;
        }
        return $result;
    }

    public function cleanUpHistoryDatabase(): void
    {
        $allEntries = $this->databaseService->findAll(AnsysSpItemSnapshot::class);
        foreach ($allEntries as $entry) {
            $this->databaseService->remove($entry);
        }
        $this->databaseService->flush();
    }

    public function cleanUpDatabase(): void
    {
        $allEntries = $this->databaseService->findAll(AnsysSpItem::class);
        foreach ($allEntries as $entry) {
            $this->databaseService->remove($entry);
        }
        $this->databaseService->flush();
    }

    public function importHistoryIntoDb(): void
    {
        $this->cleanUpHistoryDatabase();
        $projects = $this->getAllItemsFromBoard(self::BOARD_ID_PROJECTS, true);
        foreach ($projects as $project) {
            $this->logger->debug(sprintf('HISTORY - Starting with: %s', $project['name']));
            // check if we have a sp snap board
            $spSnapshotsBoardId = (int)UtilityProvider::getColumnValueFromItem($project, 'text_mkpefcyf', true);
            if ($spSnapshotsBoardId) {
                try {
                    $spSnapShotItems = $this->getAllItemsFromBoard($spSnapshotsBoardId, true);
                    $this->logger->debug('done with fetching sp ss items');
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                    $spSnapShotItems = [];
                }
                foreach ($spSnapShotItems as $spSnapShotItem) {
                    $spSnapshotMondayId = $spSnapShotItem['id'];
                    $spSnapshotAnsysCustomer = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkpjarh6', true);
                    $spSnapshotPoNumber = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkpjhbwn', true);
                    $spSnapshotDateRaw = json_decode(UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'date4'), true);
                    $spSnapshotDate = $spSnapshotDateRaw['date'];
                    $spSnapshotProjectType = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkpje09w', true);
                    $spSnapshotCurrency = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkpjmnse', true);
                    $spSnapshotOriginalBaseline = (float)UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'numeric_mkpedsg1', true);
                    $spSnapshotBaseline = (float)UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'numeric_mkpeqy39', true);
                    $spSnapshotConsumed = (float)UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'numeric_mkpenk2c', true);
                    $spSnapshotRate = (float)UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'numeric_mkpehda6', true);
                    $spSnapshotResourceType = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkpecwmx', true);
                    $spSnapshotResourceGroup = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkpjdstr', true);
                    $spSnapshotServicePartner = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkpjb40f', true);
                    $spSnapshotUniqueId = UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'text_mkperesz', true);
                    $spSnapshotConsumedUpdateDateRaw = json_decode(UtilityProvider::getColumnValueFromItem($spSnapShotItem, 'date_mkpeb8ay'), true);
                    $spSnapshotConsumedUpdateDate = $spSnapshotConsumedUpdateDateRaw['date'];
                    $spSnapshotDb = new AnsysSpItemSnapshot();
                    $spSnapshotDb
                        ->setMondayItemName($spSnapShotItem['name'])
                        ->setMondayItemId($spSnapshotMondayId)
                        ->setAnsysCustomerName($spSnapshotAnsysCustomer)
                        ->setSpPoNo($spSnapshotPoNumber)
                        ->setSnapshotDate($spSnapshotDate)
                        ->setMeasurementReportingUnit($spSnapshotProjectType)
                        ->setSpCurrency($spSnapshotCurrency)
                        ->setPlanned($spSnapshotOriginalBaseline)
                        ->setBaseline($spSnapshotBaseline)
                        ->setConsumed($spSnapshotConsumed)
                        ->setSpRate($spSnapshotRate)
                        ->setResourceType($spSnapshotResourceType)
                        ->setResourceGroup($spSnapshotResourceGroup)
                        ->setSpName($spSnapshotServicePartner)
                        ->setSpUniqueId($spSnapshotUniqueId)
                        ->setProjectName($project['name'])
                        ->setConsumedUpdateDate($spSnapshotConsumedUpdateDate);
                    $this->databaseService->persist($spSnapshotDb);
                }
            }
        }
        $this->databaseService->flush();
        $this->logger->debug('all done!');
    }

    public function importProjectsIntoDb(): void
    {
        $this->cleanUpDatabase();
        $projects = $this->getAllItemsFromBoard(self::BOARD_ID_PROJECTS, true);
        foreach ($projects as $project) {
            try {
                $this->logger->debug(sprintf('PROJECTS - Starting with: %s', $project['name']));
                // SP PP ITEMS
                $spPpBoardId = (int)UtilityProvider::getColumnValueFromItem($project, 'text_mkp79mp3', true);
                if ($spPpBoardId) {
                    $spPpItems = $this->getAllItemsFromBoard($spPpBoardId, true);
                    foreach ($spPpItems as $spPpItem) {
                        $spPpItemProjectId = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp77v4', true);
                        $spPpItemConsumedUpdateDateRaw = json_decode(UtilityProvider::getColumnValueFromItem($spPpItem, 'date4'), true);
                        $spPpItemConsumedUpdateDate = $spPpItemConsumedUpdateDateRaw['date'];
                        $spPpItemSpName = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7vn15', true);
                        $spPpItemPoNo = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7t6f4', true);
                        $spPpItemAnsysCustomerName = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7zykp', true);
                        $spPpItemProjectType = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp79jwd', true);
                        $spPpItemProjectTask = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp753na', true);
                        $spPpItemProjectActivity = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7d8cv', true);
                        $spPpItemOriginalBaseline = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkpcbzyt', true);
                        $spPpItemBaseline = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkp7kexx', true);
                        $spPpItemConsumed = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkp7wmgz', true);
                        $spPpItemSpRate = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkp7b54w', true);
                        $spPpItemCurrency = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7krca', true);
                        $spPpItemProjectStartDateRaw = json_decode(UtilityProvider::getColumnValueFromItem($spPpItem, 'date_mkp7h80n'), true);
                        $spPpItemProjectStartDate = $spPpItemProjectStartDateRaw['date'];
                        $spPpItemProjectEndDateRaw = json_decode(UtilityProvider::getColumnValueFromItem($spPpItem, 'date_mkp75ed1'), true);
                        $spPpItemProjectEndDate = $spPpItemProjectEndDateRaw['date'];
                        $spPpItemResourceType = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp75v7x', true);
                        $spPpItemUniqueId = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp758ax', true);
                        $spPpItemExecutiveSummary = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7b5b9', true);

                        $spItemDb = new AnsysSpItem();
                        $spItemDb
                            ->setMondayItemId($spPpItem['id'])
                            ->setMondayItemName($spPpItem['name'])
                            ->setAnsysCustomerName($spPpItemAnsysCustomerName)
                            ->setProjectId($spPpItemProjectId)
                            ->setMeasurementReportingUnit($spPpItemProjectType)
                            ->setConsumedUpdateDate($spPpItemConsumedUpdateDate)
                            ->setSpName($spPpItemSpName)
                            ->setSpPoNo($spPpItemPoNo)
                            ->setProjectTask($spPpItemProjectTask)
                            ->setProjectActivity($spPpItemProjectActivity)
                            ->setPlanned($spPpItemOriginalBaseline)
                            ->setBaseline($spPpItemBaseline)
                            ->setConsumed($spPpItemConsumed)
                            ->setSpRate($spPpItemSpRate)
                            ->setSpCurrency($spPpItemCurrency)
                            ->setProjectStartDate($spPpItemProjectStartDate)
                            ->setProjectEndDate($spPpItemProjectEndDate)
                            ->setSpResourceType($spPpItemResourceType)
                            ->setSpUniqueId($spPpItemUniqueId)
                            ->setProjectName($project['name'])
                            ->setExecutiveSummary($spPpItemExecutiveSummary);
                        $this->databaseService->persist($spItemDb);
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $this->logger->error($e->getTraceAsString());
                continue;
            }

        }
        $this->databaseService->flush();
    }

    public function processSpSsSnapshot(array $project, DateTime $now): ?Results
    {
        $queryResult = null;
        $spBoardId = (int)UtilityProvider::getColumnValueFromItem($project, 'text_mkp79mp3', true);
        $spSnapshotBoardId = (int)UtilityProvider::getColumnValueFromItem($project, 'text_mkpefcyf', true);
        if ($spBoardId != '' && $spSnapshotBoardId != '') {
            $spPpItems = $this->getAllItemsFromBoard($spBoardId, true);
            foreach ($spPpItems as $spPpItem) {
                $spPpAnsysCustomer = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7zykp', true);
                $spPpReference = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7t6f4', true);
                $spPPSnapshotDate = $now->format('Y-m-d');
                $spPPProjectType = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp79jwd', true);
                $spPPCurrency = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7krca', true);
                $spPPOriginalBaseline = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkpcbzyt', true);
                $spPPBaseline = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkp7kexx', true);
                $spPPConsumed = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkp7wmgz', true);
                $spPPRate = (float)UtilityProvider::getColumnValueFromItem($spPpItem, 'numeric_mkp7b54w', true);
                $spPPResourceType = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp75v7x', true);
                $spPPServicePartner = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp7vn15', true);
                $spPPUniqueId = UtilityProvider::getColumnValueFromItem($spPpItem, 'text_mkp758ax', true);
                $spPPConsumedUpdateDate = json_decode(UtilityProvider::getColumnValueFromItem($spPpItem, 'date4'), true);
                $snapshots = $this->gqlClient->getItemsByColumnValueV2($spSnapshotBoardId, 'date4', $now->format('Y-m-d'), true, 100);
                $snapshot = null;
                foreach ($snapshots as $snapshotTest) {
                    $snapShotTestId = UtilityProvider::getColumnValueFromItem($snapshotTest, 'text_mkperesz', true);
                    if ($snapShotTestId == $spPpItem['id']) {
                        $snapshot = $snapshotTest;
                        break;
                    }
                }
                $columnValues = [
                    'text_mkpjarh6' => $spPpAnsysCustomer,
                    'text_mkpjhbwn' => $spPpReference,
                    'date4' => $spPPSnapshotDate,
                    'text_mkpje09w' => $spPPProjectType,
                    'text_mkpjmnse' => $spPPCurrency,
                    'numeric_mkpedsg1' => $spPPBaseline,
                    'numeric_mkpeqy39' => $spPPOriginalBaseline,
                    'numeric_mkpenk2c' => $spPPConsumed,
                    'numeric_mkpehda6' => $spPPRate,
                    'text_mkpecwmx' => $spPPResourceType,
                    'text_mkpjb40f' => $spPPServicePartner,
                    'text_mkperesz' => $spPPUniqueId,
                    'text_mkpjdstr' => 'Service Partner',
                    'date_mkpeb8ay' => $spPPConsumedUpdateDate['date'],
                ];
                if ($snapshot !== null) {
                    $queryResult = $this->changeMultipleColumnValues($spSnapshotBoardId, $snapshot['id'], $columnValues, true);
                } else {
                    $snapshotName = sprintf('Snapshot %s', $spPPSnapshotDate);
                    $queryResult = $this->createSpSnapshotItem($spSnapshotBoardId, $snapshotName, $columnValues);
                }
            }
        }
        return $queryResult;
    }

    public function authorizeServicePartner(array $data)
    {
        $spName = $data['spname'];
        $loginMail = $data['loginemail'];
        $password = $data['loginpassword'];
        if (!$loginMail || !$password) {
            return null;
        }
        $sps = $this->getAllItemsFromGroupV2(self::BOARD_ID_SERVICE_PARTNERS, 'topics');
        $servicePartnerMonday = null;
        foreach ($sps as $sp) {
            $spmondayName = $sp['name'];
            if ($spmondayName !== $spName) {
                continue;
            }
            $spStatus = UtilityProvider::getColumnValueFromItem($sp, 'status', true);
            if ($spStatus != 'Active') {
                continue;
            }
            $spLoginMailsRaw = UtilityProvider::getColumnValueFromItem($sp, 'text_mkp7z4dy', true);
            $spLoginMailsArray = explode(';', $spLoginMailsRaw);
            $spMailRaw = json_decode(UtilityProvider::getColumnValueFromItem($sp, 'email_mknrxaqw'), true);
            $spMail = (is_array($spMailRaw) && array_key_exists('email', $spMailRaw)) ? $spMailRaw['email'] : '';
            if ($spMail != '') {
                $spLoginMailsArray[] = $spMail;
            }
            if (in_array($loginMail, $spLoginMailsArray)) {
                $spPassword = UtilityProvider::getColumnValueFromItem($sp, 'text_mknr68yq', true);
                if ($spPassword == '') {
                    continue;
                }
                if ($spPassword == $password) {
                    if (mb_strlen($spPassword) < 12) {
                        $this->session->set('loginerrors', [[
                            'severity' => 'danger',
                            'message' => 'The password that was defined for you is too short, please contact Ansys.'
                        ]] );
                        break;
                    }
                    $servicePartnerMonday = $sp;
                    break;
                }
            }
        }
        if ($servicePartnerMonday !== null) {
            return $servicePartnerMonday;
        }
        return null;
    }

    public function getProjectsForServicePartner(string $servicePartner): array
    {
        $sessionSpName = $this->session->get('spname');
        $result = [];
        $projects = $this->getItemByColumnValueV2(self::BOARD_ID_PROJECTS, 'dropdown__1', $servicePartner, true);
        $poNumberMatrix = [];
        foreach ($projects as $project) {
            $poNumberMatrix[$project['id']] = [];
            $status = UtilityProvider::getColumnValueFromItem($project, 'status', true);
            if ($status !== 'Execution' && $status !== 'Done') {
                continue;
            }
            $internalId = UtilityProvider::getColumnValueFromItem($project, 'text', true);
            $spPpBoardId = UtilityProvider::getColumnValueFromItem($project, 'text_mkp79mp3', true);
            if ($spPpBoardId !== '') {
                $existingItems = $this->getAllItemsFromBoard($spPpBoardId, true);
                foreach ($existingItems as $existingItem) {
                    $ppSpName = UtilityProvider::getColumnValueFromItem($existingItem, 'text_mkp7vn15', true);
                    if (trim(strtolower($sessionSpName)) != trim(strtolower($ppSpName))) {
                        continue;
                    }
                    $poNumber = UtilityProvider::getColumnValueFromItem($existingItem, 'text_mkp7t6f4', true);
                    if (!in_array($poNumber, $poNumberMatrix[$project['id']])) {
                        $poNumberMatrix[$project['id']][] = $poNumber;
                    }
                }
            }
            $result[] = [
                'name' => $project['name'],
                'id' => $project['id'],
                'internalId' => $internalId,
                'ppbid' => $spPpBoardId,
                'ponumbers' => implode(', ', $poNumberMatrix[$project['id']]),
            ];
        }
        return $result;
    }

    public function adjustServicePartnerPortalUrl(array $data): void
    {
        $pulseId = $data['pulseId'];
        $base64id = base64_encode($pulseId);
        $url = sprintf('https://ansys.mondayhooks.com/sploginform/%s?c=%s', $pulseId, $base64id);
        $this->changeMultipleColumnValues(self::BOARD_ID_SERVICE_PARTNERS, $pulseId, ['link_mkp41hzw' => ['url' => $url, 'text' => $url]]);
    }

    protected function addSpUploadErrorRow(array $row, int $fileRowNumber, string $column)
    {
        if (array_key_exists($fileRowNumber, $this->spUploadErrorRows)) {
            $this->spUploadErrorRows[$fileRowNumber]['column'] = sprintf('%s;%s', $this->spUploadErrorRows[$fileRowNumber]['column'], $column);
        }
        else {
            $this->spUploadErrorRows[$fileRowNumber] = [
                'file_row_number' => $fileRowNumber,
                'row' => $row,
                'column' => $column
            ];
        }
        return $this;
    }

    private function populateAvailableCurrencies(): void
    {
        $allCurrencies = $this->getAllItemsFromGroupV2(self::BOARD_ID_CURRENCY_CONFIGURATION, 'topics', 100);
        foreach ($allCurrencies as $currency) {
            $this->availableCurrencies[] = trim($currency['name']);
        }
    }

    public function processUploadedSpPpData(array $data, int $spid): array
    {
        $this->populateAvailableCurrencies();
        $serverPartnerData = $this->getItemData($spid, true);
        $spName = $serverPartnerData['name'];
        $notifications = [];
        $ppThatWillBeCreated = [];
        $projectsWithSameId = [];
        foreach ($data as $rowId => $row) {
            $rowInFile = $row['row_in_file'];
            if (!is_numeric($row['consumed_update_date'])) {
                $cudDate = DateTime::createFromFormat('Y-m-d', $row['consumed_update_date']);
                if (!$cudDate instanceof DateTime) {
                    $this->addSpUploadErrorRow($row, $rowInFile, 'consumed_update_date');
                    $notificationMessage = ['severity' => 'danger', 'message' => sprintf('ROW %s : Consumed Update Date is required!', $rowInFile)];
                    $notifications[] = $notificationMessage;
                    $consumedUpdateDate = null;
                }
                else {
                    $consumedUpdateDate = $cudDate->format('Y-m-d');
                }
            }
            else {
                $consumedUpdateDate = Date::excelToDateTimeObject($row['consumed_update_date'])->format('Y-m-d');
            }
            $row['consumed_update_date'] = $consumedUpdateDate;
            $projectStartMonth = $row['psd_month'];
            $projectStartDay = $row['psd_day'];
            $projectStartYear = $row['psd_year'];
            $projectStart = DateTime::createFromFormat('Y-m-d', sprintf('%s-%s-%s', $projectStartYear, $projectStartMonth, $projectStartDay));
            $projectEndMonth = $row['ped_month'];
            $projectEndDay = $row['ped_day'];
            $projectEndYear = $row['ped_year'];
            $projectEnd = DateTime::createFromFormat('Y-m-d', sprintf('%s-%s-%s', $projectEndYear, $projectEndMonth, $projectEndDay));
            $projectId = trim($row['project_id']);
            if ($projectId == null) {
                $this->addSpUploadErrorRow($row, $rowInFile, 'project_id');
                $notificationMessage = ['severity' => 'danger', 'message' => 'Project ID is a required column'];
                if (!in_array($notificationMessage, $notifications)) {
                    $notifications[] = $notificationMessage;
                }
                $mondayProjects = [];
            }
            else {
                $mondayProjects = $this->getItemByColumnValueV2(self::BOARD_ID_PROJECTS, 'text', $projectId, true);
            }
            if (empty($mondayProjects)) {
                $this->addSpUploadErrorRow($row, $rowInFile, 'project_id');
            }
            if (count($mondayProjects) > 1) {
                if (!in_array($projectId, $projectsWithSameId)) {
                    $notifications[] = ['severity' => 'danger', 'message' => sprintf('More than one project with ID %s was found!', $projectId)];
                }
                $this->addSpUploadErrorRow($row, $rowInFile, 'project_id');
            }
            $baseline = $row['baseline'];
            if (is_string($baseline)) {
                $this->addSpUploadErrorRow($row, $rowInFile, 'numeric_mkp7kexx');
            }
            $mondayProject = null;
            $spPpBoardId = null;
            $assignedPartners = null;
            if (isset($mondayProjects[0])) {
                $mondayProject = $mondayProjects[0];
                $spPpBoardId = UtilityProvider::getColumnValueFromItem($mondayProject, 'text_mkp79mp3', true);
            }

            if (!$projectStart instanceof DateTime) {
                $this->addSpUploadErrorRow($row, $rowInFile, 'project_start');
            }
            if (!$projectEnd instanceof DateTime) {
                $this->addSpUploadErrorRow($row, $rowInFile, 'project_end');
            }
            if ($row['sp_unique_id'] != '' && $spPpBoardId !== null) {

                $existingWorkItemOptions = $this->getItemByColumnValueV2((int)$spPpBoardId, 'text_mkp758ax', $row['sp_unique_id'], true);
                $existingWorkItem = null;
                if (isset($existingWorkItemOptions[0])) {
                    $existingWorkItem = $existingWorkItemOptions[0];
                }
                if ($existingWorkItem !== null && !array_key_exists('id', $existingWorkItem)) {
                    $this->addSpUploadErrorRow($row, $rowInFile, 'sp_unique_id');
                    $notificationMessage = ['severity' => 'danger', 'message' => sprintf('ROW %s : SP Unique ID not found in Monday: %s', $rowInFile, $row['sp_unique_id'])];
                    $notifications[] = $notificationMessage;
                }
            }
            if (!is_string($row['sppon']) || trim($row['sppon']) == '') {
                $this->addSpUploadErrorRow($row, $rowInFile, 'sppon');
            }
            if (!is_string($row['project_type']) || trim($row['project_type']) == '') {
                $this->addSpUploadErrorRow($row, $rowInFile, 'project_type');
            }
            if (!is_string($row['sp_currency']) || trim($row['sp_currency']) == '') {
                $this->addSpUploadErrorRow($row, $rowInFile, 'sp_currency');
            }
            if (!in_array(trim($row['sp_currency']), $this->availableCurrencies)) {
                $this->addSpUploadErrorRow($row, $rowInFile, 'sp_currency');
                $notificationMessage = ['severity' => 'danger', 'message' => sprintf('Currency must be one of: %s', implode(', ', $this->availableCurrencies))];
                if (!in_array($notificationMessage, $notifications)) {
                    $notifications[] = $notificationMessage;
                }
            }
            if (!is_string($row['sp_res_type']) || trim($row['sp_res_type']) == '') {
                $this->addSpUploadErrorRow($row, $rowInFile, 'sp_res_type');
            }
            if (isset($mondayProjects[0])) {
                $mondayProject = $mondayProjects[0];
                $spPpBoardId = UtilityProvider::getColumnValueFromItem($mondayProject, 'text_mkp79mp3', true);
                $assignedPartners = UtilityProvider::getColumnValueFromItem($mondayProject, 'dropdown__1', true);
                if (!str_contains($assignedPartners, $spName)) {
                    $this->addSpUploadErrorRow($row, $rowInFile, 'project_id');
                    $notificationMessage = ['severity' => 'danger', 'message' => 'Project IDs must belong to projects that your company is assigned to!'];
                    if (!in_array($notificationMessage, $notifications)) {
                        $notifications[] = $notificationMessage;
                    }
                }
                else {
                    if ($spPpBoardId === '') {
                        if (!in_array($projectId, $ppThatWillBeCreated)) {
                            $ppThatWillBeCreated[] = $projectId;
                            $notifications[] = ['severity' => 'warning', 'message' => sprintf('The plan for project with ID %s will be created!', $projectId)];
                        }
                    }
                }
            }
        }
        $this->session->set('uploadeddata', $data);
        return ['notifications' => $notifications, 'newerrors' => $this->spUploadErrorRows];
    }

    public function processUploadEdits(array $uploadEdits): array
    {
        $spid = $this->session->get('spid');
        $originalData = $this->session->get('uploadeddata');
        foreach ($uploadEdits as $uploadEdit) {
            $rowInFile = $uploadEdit['rowInFile'];
            foreach ($originalData as $key => $originalRow) {
                if ($originalRow['row_in_file'] == $rowInFile) {
                    $originalData[$key]['consumed_update_date'] = $uploadEdit['consumedUpdateDate'];
                    $originalData[$key]['sppon'] = $uploadEdit['spPoN'];
                    $originalData[$key]['project_id'] = $uploadEdit['projectId'];
                    $originalData[$key]['project_type'] = $uploadEdit['projectType'];
                    $originalData[$key]['project_task'] = $uploadEdit['projectTask'];
                    $originalData[$key]['project_activity_name'] = $uploadEdit['projectActivity'];
                    $originalData[$key]['baseline'] = (float)$uploadEdit['baseline'];
                    $originalData[$key]['consumed_actual'] = (float)$uploadEdit['consumed'];
                    $originalData[$key]['sp_rate'] = (float)$uploadEdit['spRate'];
                    $originalData[$key]['sp_currency'] = $uploadEdit['spCurrency'];
                    $originalData[$key]['psd_month'] = $uploadEdit['pStartMonth'];
                    $originalData[$key]['psd_day'] = $uploadEdit['pStartDay'];
                    $originalData[$key]['psd_year'] = $uploadEdit['pStartYear'];
                    $originalData[$key]['ped_month'] = $uploadEdit['pEndMonth'];
                    $originalData[$key]['ped_day'] = $uploadEdit['pEndDay'];
                    $originalData[$key]['ped_year'] = $uploadEdit['pEndYear'];
                    $originalData[$key]['sp_res_type'] = $uploadEdit['spResourceType'];
                    $originalData[$key]['sp_unique_id'] = $uploadEdit['spUniqueId'];
                    $originalData[$key]['executive_summary'] = $uploadEdit['executiveSummary'];
                    break;
                }
            }
        }
        $this->session->set('uploadeddata', $originalData);
        return $this->processUploadedSpPpData($originalData, $spid);
    }

    public function processFullPpData(): void
    {
        $data = $this->session->get('uploadeddata');
        $ppBoardMatrix = [];
        $currentRow = 0;
        foreach ($data as $row) {
            $consumedUpdateDate = $row['consumed_update_date'];
            if (!is_numeric($consumedUpdateDate)) {
                $cudDate = DateTime::createFromFormat('Y-m-d', $consumedUpdateDate);
                if (!$cudDate instanceof DateTime) {
                    $consumedUpdateDate = null;
                }
                else {
                    $consumedUpdateDate = $cudDate->format('Y-m-d');
                }
            }
            else {
                $consumedUpdateDate = Date::excelToDateTimeObject($consumedUpdateDate)->format('Y-m-d');
            }
            $projectStartMonth = $row['psd_month'];
            $projectStartDay = $row['psd_day'];
            $projectStartYear = $row['psd_year'];
            $projectStart = DateTime::createFromFormat('Y-m-d', sprintf('%s-%s-%s', $projectStartYear, $projectStartMonth, $projectStartDay));
            $projectEndMonth = $row['ped_month'];
            $projectEndDay = $row['ped_day'];
            $projectEndYear = $row['ped_year'];
            $projectEnd = DateTime::createFromFormat('Y-m-d', sprintf('%s-%s-%s', $projectEndYear, $projectEndMonth, $projectEndDay));
            $projectId = $row['project_id'];
            $projectPoN = $row['sppon'];
            $projectType = $row['project_type'];
            $projectTask = $row['project_task'];
            $projectActivityName = $row['project_activity_name'];
            $baseline = $row['baseline'];
            $consumed = $row['consumed_actual'];
            $spRate = $row['sp_rate'];
            $spCurrency = $row['sp_currency'];
            $spResType = $row['sp_res_type'];
            $executiveSummary = $row['executive_summary'];
            $mondayProjects = $this->getItemByColumnValueV2(self::BOARD_ID_PROJECTS, 'text', $projectId, true);
            $mondayProject = $mondayProjects[0];
            $mondayServicePartner = $this->session->get('spname');
            $mondayCustomerName = UtilityProvider::getColumnValueFromItem($mondayProject, 'text8', true);
            $mondayProjectName = $mondayProject['name'];
            if (!array_key_exists($projectId, $ppBoardMatrix)) {
                $spPpBoardId = UtilityProvider::getColumnValueFromItem($mondayProject, 'text_mkp79mp3', true);
                if ($spPpBoardId === '') {
                    // create project plan board
                    $spPpBoardId = $this->createSpPpBoard($mondayProject);
                    // create snapshot board
                    $spPpSnapshotBoardId = $this->createSpSnapshotBoard($mondayProject);
                    // update project columns
                    $this->changeMultipleColumnValues(self::BOARD_ID_PROJECTS, $mondayProject['id'], [
                        'text_mkp79mp3' => $spPpBoardId,
                        'text_mkpefcyf' => $spPpSnapshotBoardId,
                    ], true);
                }
                $ppBoardMatrix[$projectId] = $spPpBoardId;
            }
            $existingWorkItem = null;
            if ($row['sp_unique_id'] != '') {
                $existingWorkItemOptions = $this->getItemByColumnValueV2((int)$ppBoardMatrix[$projectId], 'text_mkp758ax', (int)$row['sp_unique_id'], true);
                if (isset($existingWorkItemOptions[0])) {
                    // we found an existing item
                    $existingWorkItem = $existingWorkItemOptions[0];
                }
            }
            if ($existingWorkItem !== null) {
                // prepare column values for sp pp item
                $columnValues = [
                    'text_mkp77v4' => (string)$projectId,
                    'date4' => ['date' => $consumedUpdateDate],
                    'text_mkp7vn15' => $mondayServicePartner,
                    'text_mkp7t6f4' => $projectPoN,
                    'text_mkp7zykp' => $mondayCustomerName,
                    'text_mkp79jwd' => $projectType,
                    'text_mkp753na' => $projectTask,
                    'text_mkp7d8cv' => $projectActivityName,
                    'numeric_mkp7kexx' => $baseline,
                    'numeric_mkp7wmgz' => $consumed,
                    'numeric_mkp7b54w' => $spRate,
                    'text_mkp7krca' => $spCurrency,
                    'date_mkp7h80n' => ['date' => $projectStart->format('Y-m-d')],
                    'date_mkp75ed1' => ['date' => $projectEnd->format('Y-m-d')],
                    'text_mkp75v7x' => $spResType,
                    'text_mkp7b5b9' => $executiveSummary,
                    'text_mkq0d46m' => $mondayProjectName,
                ];
                try {
                    $this->changeMultipleColumnValues($ppBoardMatrix[$projectId], $existingWorkItem['id'], $columnValues);
                } catch (Exception $e) {
                    $this->logger->debug(print_r($columnValues, true));
                    throw $e;
                }
                // now let us check if we need to update a snapshot
                $spSsBoardId = UtilityProvider::getColumnValueFromItem($mondayProject, 'text_mkpefcyf', true);
                if ($spSsBoardId !== '') {
                    // snapshot board exists, let us check if we can find the snapshot for the given update date
                    $existingSnapshotItemOptions = $this->getItemByColumnValueV2((int)$spSsBoardId, 'text_mkperesz', (int)$row['sp_unique_id'], true);
                    $challengeDate = DateTime::createFromFormat('Y-m-d', $consumedUpdateDate);
                    $challengeDate->modify('last day of this month')->setTime(23, 59, 59);
                    foreach ($existingSnapshotItemOptions as $existingSnapshotItem) {
                        $esiSnapshotDateRaw = json_decode(UtilityProvider::getColumnValueFromItem($existingSnapshotItem, 'date4'), true);
                        $esiSnapshotDate = DateTime::createFromFormat('Y-m-d', $esiSnapshotDateRaw['date']);
                        $esiSnapshotDate->modify('last day of this month')->setTime(23, 59, 59);
                        if ($challengeDate->format('Y-m-d') === $esiSnapshotDate->format('Y-m-d')) {
                            $existingSsColumnValues = [
                                'numeric_mkpedsg1' => $baseline,
                                'numeric_mkpenk2c' => $consumed,
                                'date_mkpeb8ay' => ['date' => $consumedUpdateDate]
                            ];
                            $this->changeMultipleColumnValues((int)$spSsBoardId, $existingSnapshotItem['id'], $existingSsColumnValues);
                            break;
                        }
                    }
                }
            }
            else {
                // we did not find an existing work item, so let us create a new one in the sp pp board
                $ppItemName = sprintf('%s', $mondayProjectName);
                $columnValues = [
                    'text_mkp77v4' => (string)$projectId,
                    'date4' => ['date' => $consumedUpdateDate],
                    'text_mkp7vn15' => $mondayServicePartner,
                    'text_mkp7t6f4' => $projectPoN,
                    'text_mkp7zykp' => $mondayCustomerName,
                    'text_mkp79jwd' => $projectType,
                    'text_mkp753na' => $projectTask,
                    'text_mkp7d8cv' => $projectActivityName,
                    'numeric_mkpcbzyt' => $baseline,
                    'numeric_mkp7kexx' => $baseline,
                    'numeric_mkp7wmgz' => $consumed,
                    'numeric_mkp7b54w' => $spRate,
                    'text_mkp7krca' => $spCurrency,
                    'date_mkp7h80n' => ['date' => $projectStart->format('Y-m-d')],
                    'date_mkp75ed1' => ['date' => $projectEnd->format('Y-m-d')],
                    'text_mkp75v7x' => $spResType,
                    'text_mkp7b5b9' => $executiveSummary,
                    'text_mkq0d46m' => $mondayProjectName,
                ];
                $newPpItemId = $this->createSpPpItem($ppBoardMatrix[$projectId], $ppItemName, $columnValues);
                $data[$currentRow]['sp_unique_id'] = $newPpItemId;
                $this->changeMultipleColumnValues($ppBoardMatrix[$projectId], $newPpItemId, [
                    'text_mkp758ax' => $newPpItemId
                ]);
            }
            $currentRow++;
        }
        $this->session->set('uploadeddata', $data);
    }

    public function checkForJSONUpdates(): void
    {
        $projectUpdateItem = $this->getItemData(5826823012, true);
        $projectUpdateStatus = UtilityProvider::getColumnValueFromItem($projectUpdateItem, 'status', true);
        if ($projectUpdateStatus == 'Update') {
            $this->gqlClient->changeStatusColumnValueSimple(self::BOARD_ID_DB_UPDATES, 5826823012, 'status', 'Running');
            $this->importProjectsIntoDb();
            $this->importHistoryIntoDb();
            $now = new DateTime();
            $columnValues = [
                'status' => ['label' => 'Pending'],
                'date4' => ['date' => $now->format('Y-m-d'), 'time' => $now->format('H:i:s')]
            ];
            $this->gqlClient->changeMultipleColumnValues(self::BOARD_ID_DB_UPDATES, 5826823012, $columnValues);
        }
    }

    public function getSpPpData(int $spPpBoardId, string $projectName): array
    {
        $result = [];
        $sessionSpName = $this->session->get('spname');
        $ppItems = $this->getAllItemsFromBoard($spPpBoardId, true);
        foreach ($ppItems as $ppItem) {
            $ppSpName = UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp7vn15', true);
            if (trim(strtolower($sessionSpName)) != trim(strtolower($ppSpName))) {
                continue;
            }
            $result[$ppItem['id']] = [
                'item_name' => $ppItem['name'],
                'project_id' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp77v4', true),
                'consumed_update_date' => UtilityProvider::getColumnValueFromItem($ppItem, 'date4', true),
                'sp_name' => $ppSpName,
                'sp_pon' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp7t6f4', true),
                'ansys_customer_name' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp7zykp', true),
                'project_type' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp79jwd', true),
                'project_task' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp753na', true),
                'project_activity' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp7d8cv', true),
                'original_baseline' => UtilityProvider::getColumnValueFromItem($ppItem, 'numeric_mkpcbzyt', true),
                'actual_baseline' => UtilityProvider::getColumnValueFromItem($ppItem, 'numeric_mkp7kexx', true),
                'actual_consumed' => UtilityProvider::getColumnValueFromItem($ppItem, 'numeric_mkp7wmgz', true),
                'sp_rate' => UtilityProvider::getColumnValueFromItem($ppItem, 'numeric_mkp7b54w', true),
                'sp_currency' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp7krca', true),
                'project_start' => UtilityProvider::getColumnValueFromItem($ppItem, 'date_mkp7h80n', true),
                'project_end' => UtilityProvider::getColumnValueFromItem($ppItem, 'date_mkp75ed1', true),
                'sp_res_type' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp75v7x', true),
                'sp_unique_id' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp758ax', true),
                'executive_summary' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp7b5b9', true),
                'project_name' => UtilityProvider::getColumnValueFromItem($ppItem, 'text_mkp7b5b9', true)
            ];
        }
        return $result;
    }

}
