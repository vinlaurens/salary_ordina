<?php
/**
 * Command-line tool to determine the paydays and
 * bonus days for the remainder of the year, starting
 * with the current month and ending with month 12.
 *
 * Includes checks to determine whether the bonusday,
 * which is normally the 15th of every month, is a
 * weekday. If not, the bonusday will be the following
 * wednesday from 15th of the particular month.
 *
 * Includes checks to determine the payday, which
 * is normally the last day of the month, except
 * when this day is in a weekend. If this is the case,
 * the payday will be the last thursday BEFORE the last
 * day of the month. If this day is a holiday however, the
 * payday is the first weekday before the holiday.
 *
 * Gathers all above data and ultimately outputs this data
 * to an UTF-8 CSV file with three columns, month, bonusday and payday
 * for the relative month.
 *
 * Use: php salary.php <outputfilename.csv>
 *
 * @category Development test
 * @author Vincent Laurens van den Berg
 * @version 1.0
 * @copyright 2017
 */

/**
 * Main function.
 *
 * @return int
 */
function main() {
  // Make commandline arguments available
  // from within this function.
  global $argv;
  $total_months = 12;
  $data = array();

  // Ensure current timezone
  // is set to Europe/Amsterdam.
  ini_set('date.timezone', 'Europe/Amsterdam');

  // Check if required arguments
  // are present.
  $c_argv = count($argv);
  if($c_argv == 1) {
    echo "Please specify the CSV output file for this application as argument.\n";
    return 1;
  }

  // We only accept one parameter, any more
  // parameters given will end up with this error.
  if($c_argv > 2) {
    echo "Only one parameter is allowed. Please only specify the CSV output filename.\n";
    return 1;
  }

  // Get current month and year
  // as we are outputting a CSV
  // starting with the current month
  // and ending with the remainder of
  // this year.
  $month = date('n');
  $year = date('Y');

  // Set up a new DateTime object and use this as base. DateTime objects
  // allow to navigate dates with relative ease and keeps code readable.
  $datetime = new DateTime();

  // While $month doesn't exceed $total_months...
  // (this starts with the current month and ends with month 12)
  while($month <= $total_months) {
    // Determine bonusday for this month. As it is
    // usually on the 15th of the month, let's use
    // this as the assumed initial setting.
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

  // Output the gathered data to
  // the CSV filename given as argument 1.
  outputToCsv($argv[1], $data);

  // Done. Kthxbye.
  return 0;
}

/**
 * Outputs the $data array to a CSV file.
 *
 * @param $filename
 * @param $data
 */
function outputToCsv($filename, $data) {
  // Output to csv. Open filehandler.
  $handle = fopen($filename, 'w');
  // Include UTF-8 BOM header.
  fputs($handle, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
  // Include column headers.
  fputcsv($handle, array('month', 'bonusday', 'payday'));

  // Print rows.
  foreach($data as $date => $days) {
    fputcsv($handle, array($date, $days['bonusday'], $days['payday']));
  }

  // Close file handler.
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