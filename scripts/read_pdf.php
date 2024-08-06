<?php

require_once('/application/vendor/autoload.php');

// Parse PDF file and build necessary objects.
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('/application/scripts/102279347-430.pdf');

$pdfContent = $pdf->getText();

// Skip headers
$pos = strpos($pdfContent, 'Referentni broj i opis transakcije');
$pdfContent = substr($pdfContent, $pos + strlen('Referentni broj i opis transakcije') + 1);

// print first 800 characters
//echo substr($pdfContent, 0, 800) . PHP_EOL;

$lines = explode("\n", $pdfContent);

$breakCount = 0;

$spentByCategory = [];
$countByCategory = [];

foreach ($lines as $line) {
    $payment = Payment::getPaymentFromPdfLine($line);
    if ($payment === false) {
        continue;
    }

    $balance = $payment->balance;
    $paymentAmount = $payment->paymentAmount;
    $paymentDescription = $payment->paymentDescription;
    $paymentCategory = $payment->getPaymentCategory();

    /*if ($paymentCategory === 'Investments') {
        echo "$paymentDescription $paymentAmount" . PHP_EOL;
    }*/

    $spentByCategory[$paymentCategory] = ($spentByCategory[$paymentCategory] ?? 0) + $paymentAmount;
    $countByCategory[$paymentCategory] = ($countByCategory[$paymentCategory] ?? 0) + 1;
}

$total = 0;
foreach ($spentByCategory as $category => $amount) {
    $total += $amount;
    echo "\033[32m$amount EUR\033[0m spent on \033[32m$category\033[0m ({$countByCategory["$category"]} items)" . PHP_EOL;
}
//echo "Total: $total EUR" . PHP_EOL;


class Payment
{
    private array $categoryMatches = [
        'Trajni nalog - GROUPAMA OSIGURANJE D.D.' => 'Insurance',
        'Trajni nalog za otplatu kredita' => 'Loan',
        'POS kup. MCDONALD' => 'Food & Groceries',
        'POS kup. MCDRIVE' => 'Food & Groceries',
        'POS kup. PIZZERIA MRAK' => 'Food & Groceries',
        'POS kup. KFC' => 'Food & Groceries',
        'POS kup. LIDL' => 'Food & Groceries',
        'POS kup. PLODINE' => 'Food & Groceries',
        'POS kup. TOMMY' => 'Food & Groceries',
        'POS kup. ROBIN' => 'Food & Groceries',
        'POS kup. KONZUM' => 'Food & Groceries',
        'POS kup. EUROSPIN' => 'Food & Groceries',
        'Plaæanje  ZAGREBAèKI HOLDING D.O.O.' => 'Utilities',
        'Plaæanje  ZGRADA VLADIMIRA RUZDJAKA' => 'Utilities',
        'Plaæanje  A1 Hrvatska' => 'Utilities',
        'Plaæanje  HEP - TOPLINARSTVO' => 'Utilities',
        'Plaæanje  HEP ELEKTRA' => 'Utilities',        
        'Naknada za' => 'Fees',
        'POS kup. BOLT' => 'Transport',
        'POS kup. KEKS PAY' => 'Leisure',
        'POS kup. AIRBNB' => 'Leisure',
        'POS kup. OJDANIC D.O.O' => 'Car Services',
        'POS kup. GLOVO' => 'Food & Groceries',
        'POS kup. WOLT' => 'Food & Groceries',
        'POS kup. INA' => 'Fuel',
        'POS kup. HELP.MAX.COM' => 'Subscriptions',
        'POS kup. SPOTIFY' => 'Subscriptions',
        'POS kup. NETFLIX' => 'Subscriptions',
        'POS kup. AUTOCESTA A1' => 'Transport',
        'POS kup. NP PULA/PULA/HRV' => 'Transport',
        'POS kup. NP UCKA' => 'Transport',
        'POS kup. DIONICA VRBOVSKO-' => 'Transport',
        'POS kup. NYX*MPMZIRISTOK/MOGORIC/HRV' => 'Transport',
        'POS kup. SVIJET MEDIJA AVENUE' => 'Leisure',
        'Prijenos KNEŽIÆ BLANKA' => 'Mama',
        'Plaæanje  Marina Ecimoviæ' => 'Rent',
        'Bankomat' => 'Withdrawal',
        'POS kup. WISE' => 'Investments',
    ];

    public function __construct(
        public float $balance, public float $paymentAmount, public string $paymentDescription)
    {
    }

    /*
    payment example:
    1.234,56-3,151205, POS kup. LIDL HRVATSKA DOO P-
    21/ZAGREB/HRV 3,15EUR/3,15EUR
    (29.06.,ACode:347302,Kart:6640)
    01.07.202401.07.2024
    */

    public static function getPaymentFromPdfLine(string $line): Payment|false
    {
        $balancePattern = '/^\d{1,3}(?:\.\d{3})*,\d{2}-/';
        preg_match($balancePattern, $line, $matches);
        if (empty($matches[0])) {
            return false;
        }

        $balance = substr($matches[0], 0, -1);

        $payment = substr($line, strlen($balance));
        $paymentDecimalCommaPos = strpos($payment, ',');
        $paymentAmount = substr($payment, 0, $paymentDecimalCommaPos + 3);
    
        $paymentAmount = str_replace('.', '', $paymentAmount);
        $paymentAmount = str_replace(',', '.', $paymentAmount);
        $paymentAmount = $paymentAmount * -1.0;

        $payment = substr($payment, $paymentDecimalCommaPos + 3);
        $paymentDescription = substr($payment, strpos($payment, ', ') + 2);

        $balance = str_replace('.', '', $balance);
        $balance = str_replace(',', '.', $balance);
        $balance = (float) $balance;

        return new Payment($balance, $paymentAmount, $paymentDescription);
    }

    public function getPaymentCategory(): string
    {
        $categories = array_keys($this->categoryMatches);
        foreach ($categories as $category) {
            if (str_starts_with($this->paymentDescription, $category)) {
                return $this->categoryMatches[$category];
            }
        }

        return 'Other';
    }
}