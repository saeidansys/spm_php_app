<?php

namespace App\Classes;

class UtilityProvider
{
    static public function getColumnValueFromItem(array|object $item, string $columnId, bool $getText = false, mixed $default = null)
    {
        $value = null;
        if(is_array($item)) {
            foreach ($item['column_values'] as $columnValueData) {
                if ($columnValueData['id'] === $columnId) {
                    if ($getText) {
                        if ($columnValueData['type'] == 'board_relation') {
                            $value = $columnValueData['display_value'];
                        }
                        else {
                            $value = $columnValueData['text'];
                        }
                    }
                    else {
                        $value = $columnValueData['value'];
                    }
                }
            }
        }
        else {
            foreach ($item->column_values as $columnValueData) {
                if ($columnValueData->id === $columnId) {
                    if ($getText) {
                        if ($columnValueData->type == 'board_relation') {
                            $value = $columnValueData->display_value;
                        }
                        else {
                            $value = $columnValueData->text;
                        }
                    }
                    else {
                        $value = $columnValueData->value;
                    }
                }
            }
        }
        if ($value === null && $default !== null) {
            $value = $default;
        }
        return $value;
    }
}
