<?php

namespace totum\common\Lang;

use DateTime;

class EN implements LangInterface
{
    use TranslateTrait;
    use SearchTrait;

    public const TRANSLATES = array (
  'Order field calculation errors' => 'Calculation order errors or reference to fields of deleted rows',
  'Version adding error - file for version not found' => 'Version adding error — file for version not found',
  'Creator warnings' => 'Notifications to the Administrator',
  'BFL-log is on' => 'Log of errors and external accesses enabled',
  'list-ubsubscribe-link-text' => 'Unsubscribe',
  'list-ubsubscribe-Blocked-from-sending' => 'This email is blocked from sending',
  'list-ubsubscribe-done' => 'Done',
  'list-ubsubscribe-wrong-link' => 'Wrong link',
  'OnlyOfficeSaveTimeoutError' => 'Unable to save due to lack of changes in the document. If you are editing a xlsx or another spreadsheet, please press Enter first to save the data in the editable cell or move focus to another cell.',
);
	public function dateFormat(DateTime $date, $fStr): string
    {
        $result = '';
        foreach (preg_split(
                     '/([f])/',
                     $fStr,
                     -1,
                     PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
                 ) as $split) {
            switch ($split) {
                /** @noinspection PhpMissingBreakStatementInspection */ case 'f':
                $result .= ' of ';
                $split = 'F';
                default:
                    $result .= $date->format($split);
            }
        }
        return $result;
    }

    public function num2str($num): string
    {
        $num = str_replace([',', ' '], '', trim($num));
        if (!$num) {
            return '';
        }
        $num = (int)$num;
        $words = [];
        $list1 = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven',
            'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
        ];
        $list2 = ['', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred'];
        $list3 = ['', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion',
            'octillion', 'nonillion', 'decillion', 'undecillion', 'duodecillion', 'tredecillion', 'quattuordecillion',
            'quindecillion', 'sexdecillion', 'septendecillion', 'octodecillion', 'novemdecillion', 'vigintillion'
        ];
        $num_length = strlen($num);
        $levels = (int)(($num_length + 2) / 3);
        $max_length = $levels * 3;
        $num = substr('00' . $num, -$max_length);
        $num_levels = str_split($num, 3);
        for ($i = 0; $i < count($num_levels); $i++) {
            $levels--;
            $hundreds = (int)($num_levels[$i] / 100);
            $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
            $tens = (int)($num_levels[$i] % 100);
            $singles = '';
            if ($tens < 20) {
                $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
            } else {
                $tens = (int)($tens / 10);
                $tens = ' ' . $list2[$tens] . ' ';
                $singles = (int)($num_levels[$i] % 10);
                $singles = ' ' . $list1[$singles] . ' ';
            }
            $words[] = $hundreds . $tens . $singles . (($levels && ( int )($num_levels[$i])) ? ' ' . $list3[$levels] . ' ' : '');
        } //end for loop
        $commas = count($words);
        if ($commas > 1) {
            $commas = $commas - 1;
        }
        return implode(' ', $words);
    }

    public function smallTranslit($s): string
    {
        return strtr(
            $s,
            [
			'ß'=>'ss', 'ä'=>'a', 'ü'=>'u', 'ö'=>'o', 
			'ñ'=>'ny',
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ъ' => '', 'ь' => '']
        );
    }
}