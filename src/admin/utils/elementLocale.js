/**
 * Element Plus locale built from this plugin's translations, so pagination, selects, tables,
 * dialogs and the date picker follow the site language. Element Plus fills its own {total},
 * {pager}, {count}, {max} and {column} markers, so they are passed in as placeholder values.
 * Parts of the English locale that the admin does not use are kept as they are.
 */
import en from 'element-plus/es/locale/lang/en.mjs';
import { $t, $_x } from './i18n.js';

export function elementLocale() {
    const el = en.el;

    return {
        name: 'en',
        el: {
            ...el,
            datepicker: {
                ...el.datepicker,
                now: $t('Now'),
                today: $t('Today'),
                cancel: $t('Cancel'),
                clear: $t('Clear'),
                confirm: $t('OK'),
                dateTablePrompt: $t('Use the arrow keys and enter to select the day of the month'),
                monthTablePrompt: $t('Use the arrow keys and enter to select the month'),
                yearTablePrompt: $t('Use the arrow keys and enter to select the year'),
                selectedDate: $t('Selected date'),
                selectDate: $t('Select date'),
                selectTime: $t('Select time'),
                startDate: $t('Start date'),
                startTime: $t('Start time'),
                endDate: $t('End date'),
                endTime: $t('End time'),
                prevYear: $t('Previous year'),
                nextYear: $t('Next year'),
                prevMonth: $t('Previous month'),
                nextMonth: $t('Next month'),
                month1: $t('January'),
                month2: $t('February'),
                month3: $t('March'),
                month4: $t('April'),
                month5: $t('May'),
                month6: $t('June'),
                month7: $t('July'),
                month8: $t('August'),
                month9: $t('September'),
                month10: $t('October'),
                month11: $t('November'),
                month12: $t('December'),
                weeks: {
                    sun: $t('Sun'),
                    mon: $t('Mon'),
                    tue: $t('Tue'),
                    wed: $t('Wed'),
                    thu: $t('Thu'),
                    fri: $t('Fri'),
                    sat: $t('Sat')
                },
                weeksFull: {
                    sun: $t('Sunday'),
                    mon: $t('Monday'),
                    tue: $t('Tuesday'),
                    wed: $t('Wednesday'),
                    thu: $t('Thursday'),
                    fri: $t('Friday'),
                    sat: $t('Saturday')
                },
                months: {
                    jan: $t('Jan'),
                    feb: $t('Feb'),
                    mar: $t('Mar'),
                    apr: $t('Apr'),
                    may: $_x('May', 'short month name'),
                    jun: $t('Jun'),
                    jul: $t('Jul'),
                    aug: $t('Aug'),
                    sep: $t('Sep'),
                    oct: $t('Oct'),
                    nov: $t('Nov'),
                    dec: $t('Dec')
                }
            },
            // translators: 1: characters typed, 2: the character limit
            input: { characters: $t('%1$s / %2$s characters', '{count}', '{max}') },
            inputNumber: {
                decrease: $t('Decrease number'),
                increase: $t('Increase number')
            },
            select: {
                loading: $t('Loading'),
                noMatch: $t('No matching data'),
                noData: $t('No data'),
                placeholder: $t('Select')
            },
            pagination: {
                ...el.pagination,
                goto: $t('Go to'),
                // translators: shown after the page size, as in "20/page"
                pagesize: $t('/page'),
                // translators: %s is the total number of items
                total: $t('Total %s', '{total}'),
                page: $t('Page'),
                prev: $t('Go to previous page'),
                next: $t('Go to next page'),
                // translators: %s is a page number
                currentPage: $t('page %s', '{pager}'),
                // translators: %s is a number of pages
                prevPages: $t('Previous %s pages', '{pager}'),
                // translators: %s is a number of pages
                nextPages: $t('Next %s pages', '{pager}')
            },
            dialog: { close: $t('Close this dialog') },
            messagebox: {
                title: $t('Message'),
                confirm: $t('OK'),
                cancel: $t('Cancel'),
                error: $t('Illegal input'),
                close: $t('Close this dialog')
            },
            table: {
                ...el.table,
                emptyText: $t('No data'),
                confirmFilter: $t('Confirm'),
                resetFilter: $t('Reset'),
                clearFilter: $t('All'),
                sumText: $t('Sum'),
                selectAllLabel: $t('Select all rows'),
                selectRowLabel: $t('Select this row'),
                expandRowLabel: $t('Expand this row'),
                collapseRowLabel: $t('Collapse this row'),
                // translators: %s is a column name
                sortLabel: $t('Sort by %s', '{column}'),
                // translators: %s is a column name
                filterLabel: $t('Filter by %s', '{column}')
            }
        }
    };
}
