<?php

namespace App\Service;

use App\Factory\GqlClientFactory;
use App\Factory\SiteSettingsFactory;
use GraphQL\Client;
use GraphQL\InlineFragment;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\Results;
use GraphQL\Variable;
use Throwable;

class GqlClient
{
    const DEFAULT_FETCH_AMOUNT = 100;
    const COMPLEXITY_LIMIT = 5000000;

    protected Client $client;
    protected ?Query $query;
    protected array $errors;
    protected string $token;

    public function __construct(GqlClientFactory $gqlClientFactory, SiteSettingsFactory $siteSettingsFactory)
    {
        $this->client = $gqlClientFactory->createClient();
        $this->query = null;
        $this->errors = [];
        $this->token = $siteSettingsFactory->getToken();
    }

    public function runQuery(?Query $query = null, bool $resultsAsArray = false, array $variables = [], bool $checkComplexity = true): Results
    {
        try {
            $result = $this->client->runQuery(($query !== null) ? $query : $this->query, $resultsAsArray, $variables);
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
            sleep(1);
            $result = $this->client->runQuery(($query !== null) ? $query : $this->query, $resultsAsArray, $variables);
        }
        if ($checkComplexity) {
            $complexity = $this->checkComplexity();
            if ($complexity > 0) {
                sleep($complexity);
            }
        }
        return $result;
    }

    public function changeStatusColumnValueSimple(int $boardId, int $pulseId, string $columnId, string $newLabel): void
    {
        $updateGql = (new Mutation('change_simple_column_value'))
            ->setArguments([
                'board_id' => $boardId,
                'item_id' => $pulseId,
                'column_id' => $columnId,
                'value' => $newLabel
            ])
            ->setSelectionSet([
                'id',
            ]);
        $this->runQuery($updateGql, true);
    }

    public function getItemData(int $id, bool $asArray = false)
    {
        $newItemData = (new Query('items'))
            ->setArguments([
                'ids' => $id
            ])
            ->setSelectionSet([
                'id',
                'name',
                (new Query('parent_item'))
                    ->setSelectionSet(['id', 'name']),
                (new Query('column_values'))
                    ->setSelectionSet([
                        'id',
                        'value',
                        'text',
                        'type',
                        (new Query('column'))
                            ->setSelectionSet([
                                'id', 'title'
                            ]),
                        (new InlineFragment('BoardRelationValue'))
                            ->setSelectionSet(
                                [
                                    'display_value',
                                ]
                            ),
                        (new InlineFragment('MirrorValue'))
                            ->setSelectionSet(
                                [
                                    'display_value'
                                ]
                            ),
                    ]),
                (new Query('group'))
                    ->setSelectionSet(['id', 'title']),
                (new Query('board'))
                    ->setSelectionSet(['id']),
            ]);
        $result = $this->runQuery($newItemData, $asArray);
        $resultData = $result->getData();
        if ($asArray === true) {
            if (isset($resultData['items'][0])) {
                $returnValue = $resultData['items'][0];
            }
            else {
                $returnValue = [];
            }
        }
        else {
            $returnValue = $result->getData()->items[0];
        }
        return $returnValue;
    }

    public function getAllItemsFromBoardV2(int $boardId, bool $asArray = false): array
    {
        $gql = (new Query('boards'))
            ->setArguments([
                'ids' => $boardId
            ])
            ->setSelectionSet([
                (new Query('items_page'))
                    ->setArguments([
                        'limit' => self::DEFAULT_FETCH_AMOUNT,
                    ])
                    ->setSelectionSet(
                        [
                            'cursor',
                            (new Query('items'))
                                ->setSelectionSet(
                                    [
                                        'id',
                                        'name',
                                        (new Query('column_values'))
                                            ->setSelectionSet([
                                                'id',
                                                'value',
                                                'text',
                                                'type',
                                                (new Query('column'))
                                                    ->setSelectionSet([
                                                        'id', 'title'
                                                    ]),
                                                (new InlineFragment('BoardRelationValue'))
                                                    ->setSelectionSet(
                                                        [
                                                            'display_value',
                                                        ]
                                                    ),
                                                (new InlineFragment('MirrorValue'))
                                                    ->setSelectionSet(
                                                        [
                                                            'display_value'
                                                        ]
                                                    ),
                                            ]),
                                        (new Query('group'))
                                            ->setSelectionSet(['id', 'title']),
                                    ]
                                ),
                        ]
                    ),
            ]);
        $initialResult = $this->runQuery($gql, true);
        $irData = $initialResult->getData();
        if (!isset($irData['boards'][0])) {
            $items = [];
        }
        else {
            $cursor = $irData['boards'][0]['items_page']['cursor'];
            $items = $irData['boards'][0]['items_page']['items'];
            while ($cursor != null) {
                $gql = (new Query('next_items_page'))
                    ->setArguments([
                        'limit' => self::DEFAULT_FETCH_AMOUNT,
                        'cursor' => $cursor
                    ])
                    ->setSelectionSet([
                        'cursor',
                        (new Query('items'))
                            ->setSelectionSet(
                                [
                                    'id',
                                    'name',
                                    (new Query('column_values'))
                                        ->setSelectionSet([
                                            'id',
                                            'value',
                                            'text',
                                            'type',
                                            (new Query('column'))
                                                ->setSelectionSet([
                                                    'id', 'title'
                                                ]),
                                            (new InlineFragment('BoardRelationValue'))
                                                ->setSelectionSet(
                                                    [
                                                        'display_value',
                                                    ]
                                                ),
                                            (new InlineFragment('MirrorValue'))
                                                ->setSelectionSet(
                                                    [
                                                        'display_value'
                                                    ]
                                                ),
                                        ]),
                                    (new Query('group'))
                                        ->setSelectionSet(['id', 'title']),
                                ]
                            ),
                    ]);
                $result = $this->runQuery($gql, true);
                $resultData = $result->getData();
                $cursor = $resultData['next_items_page']['cursor'];
                $currentItems = $resultData['next_items_page']['items'];
                $items = array_merge($items, $currentItems);
            }
        }
        return $items;
    }

    public function getItemsByColumnValueV2(
        int $boardId,
        string $columnId,
        string $columnValue,
        bool $asArray = false,
        int $limit = GqlClient::DEFAULT_FETCH_AMOUNT
    ){
        $gql = (new Query('items_page_by_column_values'))
            ->setVariables([
                new Variable('boardid', 'ID', true),
                new Variable('columns', '[ItemsPageByColumnValuesQuery!]', true),
                new Variable('limit', 'Int', true),
            ])
            ->setArguments([
                'board_id' => '$boardid',
                'columns' => '$columns',
                'limit' => '$limit'
            ])
            ->setSelectionSet([
                'cursor',
                (new Query('items'))
                    ->setSelectionSet(
                        [
                            'id',
                            'name',
                            (new Query('column_values'))
                                ->setSelectionSet([
                                    'id',
                                    'value',
                                    'text',
                                    'type',
                                    (new Query('column'))
                                        ->setSelectionSet([
                                            'id', 'title'
                                        ]),
                                    (new InlineFragment('BoardRelationValue'))
                                        ->setSelectionSet(
                                            [
                                                'display_value',
                                            ]
                                        ),
                                    (new InlineFragment('MirrorValue'))
                                        ->setSelectionSet(
                                            [
                                                'display_value'
                                            ]
                                        ),
                                    ]),
                            (new Query('group'))
                                ->setSelectionSet(['id', 'title']),
                        ]
                    ),
            ]);
        $columns = [['column_id' => $columnId, 'column_values' => $columnValue]];
        $initialResult = $this->runQuery($gql, $asArray, ['columns' => $columns, 'boardid' => $boardId, 'limit' => $limit]);
        $irData = $initialResult->getData();
        if (!isset($irData['items_page_by_column_values']['items'])) {
            $items = [];
        }
        else {
            $cursor = $irData['items_page_by_column_values']['cursor'];
            $items = $irData['items_page_by_column_values']['items'];
            while ($cursor != null) {
                $gql = (new Query('next_items_page'))
                    ->setArguments([
                        'limit' => self::DEFAULT_FETCH_AMOUNT,
                        'cursor' => $cursor
                    ])
                    ->setSelectionSet([
                        'cursor',
                        (new Query('items'))
                            ->setSelectionSet(
                                [
                                    'id',
                                    'name',
                                    (new Query('column_values'))
                                        ->setSelectionSet([
                                            'id',
                                            'value',
                                            'text',
                                            'type',
                                            (new Query('column'))
                                                ->setSelectionSet([
                                                    'id', 'title'
                                                ]),
                                            (new InlineFragment('BoardRelationValue'))
                                                ->setSelectionSet(
                                                    [
                                                        'display_value',
                                                    ]
                                                ),
                                            (new InlineFragment('MirrorValue'))
                                                ->setSelectionSet(
                                                    [
                                                        'display_value'
                                                    ]
                                                ),
                                        ]),
                                ]
                            ),
                    ]);
                $result = $this->runQuery($gql, true);
                $resultData = $result->getData();
                $cursor = $resultData['next_items_page']['cursor'];
                $currentItems = $resultData['next_items_page']['items'];
                $items = array_merge($items, $currentItems);
            }
        }
        return $items;
    }

    public function getAllItemsFromGroupV2(
        int $boardId,
        string $groupId,
        int $limit = GqlClient::DEFAULT_FETCH_AMOUNT
    ): array
    {
        $gql = (new Query('boards'))
            ->setVariables([
                new Variable('queryparams', 'ItemsQuery')
            ])
            ->setArguments([
                'ids' => $boardId
            ])
            ->setSelectionSet([
                (new Query('items_page'))
                    ->setArguments([
                        'limit' => $limit,
                        'query_params' => '$queryparams'
                    ])
                    ->setSelectionSet(
                        [
                            'cursor',
                            (new Query('items'))
                                ->setSelectionSet(
                                    [
                                        'id',
                                        'name',
                                        (new Query('column_values'))
                                            ->setSelectionSet([
                                                'id',
                                                'value',
                                                'type',
                                                'text',
                                                (new Query('column'))
                                                    ->setSelectionSet([
                                                        'id', 'title'
                                                    ]),
                                                (new InlineFragment('BoardRelationValue'))
                                                    ->setSelectionSet(
                                                        [
                                                            'display_value',
                                                        ]
                                                    ),
                                                (new InlineFragment('MirrorValue'))
                                                    ->setSelectionSet(
                                                        [
                                                            'display_value'
                                                        ]
                                                    ),
                                                ]),
                                        (new Query('group'))
                                            ->setSelectionSet(['id', 'title']),
                                    ]
                                ),
                        ]
                    ),
            ]);
        $queryParams = ['rules' => [ ['column_id' => 'group', 'compare_value' => [ $groupId ], 'operator' => 'any_of' ] ], 'operator' => 'or'];
        $initialResult = $this->runQuery($gql, true, ['queryparams' => $queryParams]);
        $irData = $initialResult->getData();
        $cursor = $irData['boards'][0]['items_page']['cursor'];
        $items = $irData['boards'][0]['items_page']['items'];
        while ($cursor != null) {
            $gql = (new Query('next_items_page'))
                ->setArguments([
                    'limit' => $limit,
                    'cursor' => $cursor
                ])
                ->setSelectionSet([
                    'cursor',
                    (new Query('items'))
                        ->setSelectionSet(
                            [
                                'id',
                                'name',
                                (new Query('column_values'))
                                    ->setSelectionSet([
                                        'id',
                                        'value',
                                        'type',
                                        'text',
                                        (new Query('column'))
                                            ->setSelectionSet([
                                                'id', 'title'
                                            ]),
                                        (new InlineFragment('BoardRelationValue'))
                                            ->setSelectionSet(
                                                [
                                                    'display_value',
                                                ]
                                            ),
                                        (new InlineFragment('MirrorValue'))
                                            ->setSelectionSet(
                                                [
                                                    'display_value'
                                                ]
                                            ),
                                    ]),
                                (new Query('group'))
                                    ->setSelectionSet(['id', 'title']),
                            ]
                        ),
                ]);
            $result = $this->runQuery($gql, true);
            $resultData = $result->getData();
            $cursor = $resultData['next_items_page']['cursor'];
            $currentItems = $resultData['next_items_page']['items'];
            $items = array_merge($items, $currentItems);
        }
        return $items;
    }

    public function checkComplexity(): int
    {
        $getComplexity = (new Query('complexity'))
            ->setSelectionSet([
                'before',
                'after',
                'query',
                'reset_in_x_seconds ',
            ]);
        $resultComplexity = $this->runQuery($getComplexity, true, [], false);
        $complexityResults = $resultComplexity->getData()['complexity'];
        if ((int)$complexityResults['after'] < self::COMPLEXITY_LIMIT) {
            return (int)$complexityResults['reset_in_x_seconds'];
        }
        return 0;
    }

    public function changeMultipleColumnValues(int $boardId, int $itemId, array $columnValues, bool $createLabelsIfMissing = false): Results
    {
        $gql = (new Mutation('change_multiple_column_values'))
            ->setVariables([
                new Variable('column_values', 'JSON', true),
            ])
            ->setArguments([
                'board_id' => $boardId,
                'item_id' => $itemId,
                'column_values' => '$column_values',
                'create_labels_if_missing' => $createLabelsIfMissing
            ])
            ->setSelectionSet([
                'id',
            ]);
        return $this->runQuery($gql, true, [
            'column_values' => json_encode($columnValues)
        ]);
    }

}
