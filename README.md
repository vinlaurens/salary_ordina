# salary_ordina
Script to determine paydays and bonusdays for Ordina

This script will output a CSV (UTF-8) formatted file with bonusdays and paydays for each remaining month of the year (currently including the current month). It will return three columns in the CSV; month/year, bonusday in that month, payday in that month.

## Execution
To execute the script, use a command line interface and execute the following. The script requires the name of the output CSV file as the single parameter. It will error out both when this parameter is omitted or when more than 1 parameters are given.

`code` php salary.php output.csv
