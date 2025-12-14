<?php

namespace App\Service;

use App\Classes\UtilityProvider;
use App\Factory\LoggerFactory;
use App\Factory\SiteSettingsFactory;
use GraphQL\Mutation;
use GraphQL\Results;
use GraphQL\Variable;
use Psr\Log\LoggerInterface;

class BaseCustomerService extends BaseService
{

    protected LoggerInterface $logger;
    protected GqlClient $gqlClient;
    protected string $host;
    protected string $serviceUrl;
    protected array $spPpBoardOwners;
    protected array $spPpBoardTeamOwners;
    protected array $spPpBoardMembers;
    protected array $spPpBoardTeamMembers;
    protected array $spSnapshotBoardOwners;
    protected array $spSnapshotBoardTeamOwners;
    protected array $spSnapshotBoardMembers;
    protected array $spSnapshotBoardTeamMembers;

    public function __construct(
        LoggerFactory $loggerFactory,
        GqlClient $gqlClient,
        SiteSettingsFactory $siteSettingsFactory
    ){
        $this->logger = $loggerFactory->addFileHandler('service.log')->createLogger();
        $this->gqlClient = $gqlClient;
        $this->serviceUrl = $siteSettingsFactory->getServiceUrl();
        $this->host = $siteSettingsFactory->getSiteUrl();
    }

    public function getTokenForRoute(int $tokenItemId)
    {
        $item = $this->getItemData($tokenItemId);
        return UtilityProvider::getColumnValueFromItem($item, 'text', true);
    }

    public function getAllItemsFromGroupV2(
        int $boardId,
        string $groupId,
        int $limit = GqlClient::DEFAULT_FETCH_AMOUNT
    ): array
    {
        return $this->gqlClient->getAllItemsFromGroupV2($boardId, $groupId, $limit);
    }

    public function getAllItemsFromBoard(int $boardId, bool $asArray = false): array
    {
        return $this->gqlClient->getAllItemsFromBoardV2($boardId, $asArray);
    }

    public function getItemByColumnValueV2(
        int $boardId,
        string $columnId,
        string $columnValue,
        bool $asArray = false,
        int $limit = GqlClient::DEFAULT_FETCH_AMOUNT
    ){
        return $this->gqlClient->getItemsByColumnValueV2($boardId, $columnId, $columnValue, $asArray, $limit);
    }

    public function getItemData(int $id, bool $asArray = false)
    {
        return $this->gqlClient->getItemData($id, $asArray);
    }

    public function changeMultipleColumnValues(int $boardId, int $itemId, array $columnValues, bool $createLabelsIfMissing = false): Results
    {
        return $this->gqlClient->changeMultipleColumnValues($boardId, $itemId, $columnValues, $createLabelsIfMissing);
    }

    public function createSpPpBoard(array $mondayObject)
    {
        // check additional owners and members
        $this->populateSpPpBoardMembers($mondayObject);
        // create project plan board
        $newBoardGql = (new Mutation('create_board'))
            ->setVariables([
                new Variable('board_kind', 'BoardKind', true),
            ])
            ->setArguments([
                'board_name' => sprintf('%s - SPPP', $mondayObject['name']),
                'board_kind' => '$board_kind',
                'board_subscriber_ids' => $this->spPpBoardMembers,
                'board_subscriber_teams_ids' => $this->spPpBoardTeamMembers,
                'board_owner_ids' => $this->spPpBoardOwners,
                'board_owner_team_ids ' => $this->spPpBoardTeamOwners,
                'folder_id' => 16806015,
                'template_id' => 11459439
            ])
            ->setSelectionSet([
                'id'
            ]);
        $resultData = $this->gqlClient->runQuery($newBoardGql, true, ['board_kind' => 'share']);
        return $resultData->getData()['create_board']['id'];
    }

    private function populateSpPpBoardMembers(array $mondayObject): void
    {
        // check additional owners and members
        $this->spPpBoardMembers = [];
        $this->spPpBoardOwners = [45821510];
        $this->spPpBoardTeamOwners = [986014, 991950, 1005662];
        $this->spPpBoardTeamMembers = [940836];
        $this->spSnapshotBoardMembers = [];
        $this->spSnapshotBoardOwners = [45821510];
        $this->spSnapshotBoardTeamOwners = [986014, 1005662];
        $this->spSnapshotBoardTeamMembers = [];
        if (UtilityProvider::getColumnValueFromItem($mondayObject, 'people93', true) != '') {
            $projectManagersData = json_decode(UtilityProvider::getColumnValueFromItem($mondayObject, 'people93'), true);
            $projectManagersPaP = $projectManagersData['personsAndTeams'];
            foreach ($projectManagersPaP as $projectManager) {
                if ($projectManager['kind'] == 'person') {
                    $this->spPpBoardOwners[] = $projectManager['id'];
                    $this->spSnapshotBoardOwners[] = $projectManager['id'];
                }
                if ($projectManager['kind'] == 'team') {
                    $this->spPpBoardTeamOwners[] = $projectManager['id'];
                    $this->spSnapshotBoardTeamOwners[] = $projectManager['id'];
                }
            }
        }
        if (UtilityProvider::getColumnValueFromItem($mondayObject, 'people7', true) != '') {
            $ccsData = json_decode(UtilityProvider::getColumnValueFromItem($mondayObject, 'people7'), true);
            $ccsPaP = $ccsData['personsAndTeams'];
            foreach ($ccsPaP as $cc) {
                if ($cc['kind'] == 'person') {
                    $this->spPpBoardMembers[] = $cc['id'];
                }
                if ($cc['kind'] == 'team') {
                    $this->spPpBoardTeamMembers[] = $cc['id'];
                }
            }
        }
    }

    public function createSpSnapshotBoard(array $mondayObject)
    {
        $this->populateSpPpBoardMembers($mondayObject);
        // create snapshot board
        $newSnBoardGql = (new Mutation('create_board'))
            ->setVariables([
                new Variable('board_kind', 'BoardKind', true),
            ])
            ->setArguments([
                'board_name' => sprintf('%s - SPSS', $mondayObject['name']),
                'board_kind' => '$board_kind',
                'board_owner_ids' => $this->spSnapshotBoardOwners,
                'board_owner_team_ids ' => $this->spSnapshotBoardTeamOwners,
                'folder_id' => 16900457,
                'template_id' => 11495204
            ])
            ->setSelectionSet([
                'id'
            ]);
        $resultDataSn = $this->gqlClient->runQuery($newSnBoardGql, true, ['board_kind' => 'share']);
        return $resultDataSn->getData()['create_board']['id'];
    }

    public function createSpPpItem($boardId, string $itemName, array $columnValues)
    {
        $newSpPpItemGql = (new Mutation('create_item'))
            ->setVariables([
                new Variable('input_name', 'String', true),
                new Variable('columnvalues', 'JSON', true),
            ])
            ->setArguments([
                'board_id' => $boardId,
                'item_name' => '$input_name',
                'column_values' => '$columnvalues',
                'create_labels_if_missing' => true
            ])
            ->setSelectionSet([
                'id',
            ]);
        $createResult = $this->gqlClient->runQuery($newSpPpItemGql, true, [
            'input_name' => $itemName,
            'columnvalues' => json_encode($columnValues)]);
        return $createResult->getData()['create_item']['id'];
    }

    public function createSpSnapshotItem($boardId, string $itemName, array $columnValues): Results
    {
        $newSnapshotGql = (new Mutation('create_item'))
            ->setVariables([
                new Variable('input_name', 'String', true),
                new Variable('columnvalues', 'JSON', true),
            ])
            ->setArguments([
                'board_id' => $boardId,
                'item_name' => '$input_name',
                'column_values' => '$columnvalues',
                'create_labels_if_missing' => true
            ])
            ->setSelectionSet([
                'id',
            ]);
        return $this->gqlClient->runQuery($newSnapshotGql, true, [
            'input_name' => $itemName,
            'columnvalues' => json_encode($columnValues)]);
    }

}
