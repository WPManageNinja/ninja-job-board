<?php

namespace WPJobBoard\Classes;

use WPJobBoard\Classes\Entry\Entry;
use WPJobBoard\Classes\Entry\MetaData;
use WPJobBoard\Classes\Models\Forms;
use WPJobBoard\Classes\Models\OrderItem;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Placeholder Parser for a submission
 * @since 1.0.0
 */
class PlaceholderParser
{
    /**
     * @param $string
     * @param $submission
     *
     * Possible Submission Parameters: [can be found on submission.ATRRIBUTE_NAME]
     * {submission.id}
     * {submission.submission_hash}
     * {submission.applicant_name}
     * {submission.applicant_email}
     * {submission.application_status}
     *
     * Possible Input Item Parameters [can be found on submission.form_data_formatted.INPUTNAME]
     * {input.INPUTNAME}
     *
     * @param string $context Where the result goes: 'html' (values are HTML-escaped), 'url' (values are URL-encoded)
     *                        or 'text' (raw values; the caller must sanitize the result for its own context).
     */
    public static function parse($string, $submission, $context = 'html')
    {
        if (!is_string($string) || $string === '') {
            return '';
        }

        $parsables = self::parseShortcode($string);
        if (!$parsables) {
            return $string;
        }

        $formattedParsables = array();
        foreach ($parsables as $parsableKey => $parsable) {
            // Get Parsed Group
            $group = strtok($parsable, '.:');
            $itemExt = str_replace(array($group . '.', $group . ':'), '', $parsable);
            $formattedParsables[$group][$parsableKey] = $itemExt;
        }

        $entry = new Entry($submission);

        $sunmissionPlaceholders = ArrayHelper::only($formattedParsables, array(
            'input', 'submission'
        ));
        $submissionParseItems = self::parseInpuFields($sunmissionPlaceholders, $entry, $context);

        $wpPlaceholders = ArrayHelper::only($formattedParsables, array(
            'wp', 'post_meta', 'user_meta', 'querystring', 'other'
        ));

        $wpParseItems = self::parseWPFields($wpPlaceholders, $entry);
        $wpParseItems = array_map(function ($value) use ($context) {
            return self::escapeValue($value, $context);
        }, $wpParseItems);

        $parseItems = array_merge($submissionParseItems, $wpParseItems);

        $parseItems = apply_filters('wpjobboard/submission_placeholders_parsed', $parseItems, $submission, $parsables);

        $formatedParsedItems = [];
        foreach ($parseItems as $parsedKey => $parseItem) {
            if(is_array($parseItem)) {
                $parseItem = implode(', ', $parseItem);
            }
            $formatedParsedItems[$parsedKey] = $parseItem;
        }

        return str_replace(array_keys($formatedParsedItems), array_values($formatedParsedItems), $string);
    }

    public static function parseInpuFields($placeholders, $entry, $context = 'html')
    {
        $parsedData = array();

        foreach ($placeholders as $groupKey => $values) {
            foreach ($values as $placeholder => $targetItem) {
                if ($groupKey == 'input') {
                    if ($context == 'html') {
                        $parsedData[$placeholder] = $entry->getInputHtml($targetItem);
                    } else {
                        $parsedData[$placeholder] = self::escapeValue($entry->getRawFormattedInput($targetItem), $context);
                    }
                } else if ($groupKey == 'submission') {
                    $value = $entry->{$targetItem};
                    if ($context == 'html' && substr($targetItem, -5) === '_html') {
                        // Built by the plugin from escaped values
                        $parsedData[$placeholder] = wpJobBoardKsesWithStyle($value);
                    } else {
                        $parsedData[$placeholder] = self::escapeValue($value, $context);
                    }
                }
            }
        }
        return $parsedData;
    }

    /**
     * Escape a placeholder value for the target context
     * @param mixed  $value
     * @param string $context
     * @return string
     */
    public static function escapeValue($value, $context)
    {
        if (is_array($value)) {
            $value = implode(', ', array_filter($value, 'is_scalar'));
        } else if (!is_scalar($value)) {
            $value = '';
        }

        $value = (string)$value;

        if ($context == 'html') {
            return esc_html($value);
        }

        if ($context == 'url') {
            return rawurlencode($value);
        }

        return $value;
    }

    public static function parseWPFields($placeHolders, $entry)
    {
        $parsedData = array();
        $metaData = new MetaData($entry);
        foreach ($placeHolders as $groupKey => $values) {
            foreach ($values as $placeholder => $targetItem) {
                if ($groupKey == 'wp') {
                    $parsedData[$placeholder] = $metaData->getWPValues($targetItem);
                } else if ($groupKey == 'post_meta') {
                    $parsedData[$placeholder] = $metaData->getPostMeta($targetItem);
                } else if ($groupKey == 'user_meta') {
                    $parsedData[$placeholder] = $metaData->getuserMeta($targetItem);
                } else if ($groupKey == 'querystring') {
                    $parsedData[$placeholder] = $metaData->getFromUrlQuery($targetItem);
                } else if ($groupKey == 'other') {
                    $parsedData[$placeholder] = $metaData->getOtherData($targetItem);
                }
            }
        }
        return $parsedData;
    }

    public static function parseShortcode($string)
    {
        $parsables = [];
        preg_replace_callback('/{+(.*?)}/', function ($matches) use (&$parsables) {
            $parsables[$matches[0]] = $matches[1];
        }, $string);
        return $parsables;
    }
}