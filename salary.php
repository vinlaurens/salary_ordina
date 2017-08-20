<?php

/**
 * Main function.
 *
 * @return int
 */
function main() {
   global $argv;
   $total_months = 12;
   $data = array();

   // Set timezone
   ini_set('date.timezone', 'Europe/Amsterdam');

   // Check arguments
   $c_argv = count($argv);
   if($c_argv == 1) {
      echo "Please specify the CSV output file for this application as argument.\n";
      return 1;
   }

   // We only accept one parameter
   if($c_argv > 2) {
      echo "Only one parameter is allowed. Please only specify the CSV output filename.\n";
      return 1;
   }

   $month = date('n');
   $year = date('Y');

   // Set up a new DateTime object.
   $datetime = new DateTime();

   while($month <= $total_months) {
        // Determine bonusday for this month. As it is
        // usually on the 15th of the month, let's use
        // this as the initial setting.
        $datetime->setDate($year, $month, 15);
        // Determine real bonus day and add bonus day to data array.
        $data[$month.'-'.$year]['bonusday'] = getBonusDay($datetime);

        // Determine payday for this month.
        $last_day = $datetime->format('t');
        // Initially, the last day of the month is
        // used as payday.
        $datetime->setDate($year, $month, $last_day);
        // Determine the real payday. Not taking holidays
        // into account yet.
        $payday = getPayDay($datetime);

        // Check if payday is a holiday. If so,
        // we select the last weekday before the holiday.
        $datetime->setDate($year, $month, $payday);
        if(isHoliday($datetime)) {
            $payday = getWeekDayBeforeHoliday($datetime);
        }

        // Add payday to data array.
        $data[$month.'-'.$year]['payday'] = $payday;

        // Increment month.
        $month++;
   }

   outputToCsv($argv[1], $data);

   return 0;
}

/**
 * Outputs the $data array to a CSV file.
 *
 * @param $filename
 * @param $data
 */
function outputToCsv($filename, $data) {
    // Output to csv
   $handle = fopen($filename, 'w');
   // UTF-8
   fputs($handle, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
   fputcsv($handle, array('month', 'bonusday', 'payday'));

   foreach($data as $date => $days) {
      fputcsv($handle, array($date, $days['bonusday'], $days['payday']));
   }

   fclose($handle);
}

/**
 * Determines the bonusday. If 15th is not a weekday,
 * this will determine the next wednesday as bonusday.
 *
 * @param DateTime $datetime
 * @return int|string
 */
function getBonusDay(DateTime $datetime) {
    // Get bonusday
    if(isWeekDay($datetime)) {
        $bonusday = 15;
    } else {
        while($datetime->format('D') != 'Wed') {
            $datetime->modify('+1 day');
        }
        $bonusday = $datetime->format('d');
    }

    return $bonusday;
}

/**
 * Determines the payday. If last day is a weekday
 * it will use last day. If not, it will return last
 * thursday before last day of the month.
 *
 * @param DateTime $datetime
 * @return string
 */
function getPayDay(DateTime $datetime) {
    // Get payday
    if(isWeekDay($datetime)) {
        $payday = $datetime->format('d');
    } else {
        while($datetime->format('D') != 'Thu') {
            $datetime->modify('-1 day');
        }
        $payday = $datetime->format('d');
    }

    return $payday;
}

/**
 * Gets the last weekday before a holiday.
 *
 * @param DateTime $datetime
 * @return string
 */
function getWeekDayBeforeHoliday(DateTime $datetime) {
    while(isHoliday($datetime) || !isWeekDay($datetime)) {
        $datetime->modify('-1 day');
    }

    return $datetime->format('d');
}

/**
 * Determines whether a given date in a DateTime object
 * is a weekday.
 *
 * @param DateTime $datetime
 * @return bool
 */
function isWeekDay(DateTime $datetime) {
    $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
    if(in_array($datetime->format('D'), $weekdays)) {
        return true;
    }

    return false;
}

/**
 * Determines whether a given date in a DateTime object
 * is a holiday. It uses an array to determine which days
 * are holidays.
 *
 * @param DateTime $datetime
 * @return bool
 */
function isHoliday(DateTime $datetime) {
    // Array of holidays in format
    // d-m (day-month). More holidays
    // can be added here in this format
    // if desired.
    $holidays = [
    '25-12',
    '31-12',
    date('d-m', easter_date($datetime->format('Y'))),
    ];

    if(in_array($datetime->format('d-m'), $holidays)) {
        return true;
    }

    return false;
}

// Call the main() function.
main();
