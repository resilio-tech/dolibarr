<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/FactureFournisseurTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture-rec.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/paiementfourn.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__).'/../../htdocs/multicurrency/class/multicurrency.class.php';
require_once dirname(__FILE__).'/../../htdocs/multicurrency/class/currencyrate.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FactureFournisseurTest extends CommonClassTest
{
	/**
	 * testFactureFournisseurCreate
	 *
	 * @return int
	 */
	public function testFactureFournisseurCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureFournisseur($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testFactureFournisseurFetch
	 *
	 * @param	int		$id		If supplier invoice
	 * @return	void
	 *
	 * @depends	testFactureFournisseurCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureFournisseur($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testFactureFournisseurUpdate
	 *
	 * @param	FactureFournisseur	$localobject	Supplier invoice
	 * @return	int
	 *
	 * @depends	testFactureFournisseurFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note = 'New note after update';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject;
	}

	/**
	 * testFactureFournisseurValid
	 *
	 * @param	FactureFournisseur	$localobject	Supplier invoice
	 * @return	void
	 *
	 * @depends	testFactureFournisseurUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->validate($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject;
	}

	/**
	 * testFactureFournisseurOther
	 *
	 * @param	FactureFournisseur	$localobject		Supplier invoice
	 * @return	void
	 *
	 * @depends testFactureFournisseurValid
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testFactureFournisseurDelete
	 *
	 * @param	int		$id		Id of supplier invoice
	 * @return	void
	 *
	 * @depends	testFactureFournisseurOther
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFournisseurDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureFournisseur($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $result;
	}

	/**
	 * testFactureFournisseurPaymentReference
	 *
	 * The payment reference given by the supplier is checked (QR reference or RF), then stored and read back.
	 *
	 * @return void
	 */
	public function testFactureFournisseurPaymentReference()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertSame('', FactureFournisseur::checkPaymentReference(''));
		$this->assertSame('', FactureFournisseur::checkPaymentReference('210000000003139471430009017'));
		$this->assertSame('', FactureFournisseur::checkPaymentReference('RF18539007547034'));
		$this->assertSame('ErrorQRReferenceNotValid', FactureFournisseur::checkPaymentReference('210000000003139471430009018'));
		$this->assertSame('ErrorPaymentReferenceOfAccountNotValid', FactureFournisseur::checkPaymentReference('RF19539007547034'));

		$localobject = new FactureFournisseur($db);
		$localobject->initAsSpecimen();
		$this->assertGreaterThan(0, $localobject->create($user), $localobject->errorsToString());
		$localobject->fetch($localobject->id);

		$localobject->payment_reference = '210000000003139471430009017';
		$this->assertGreaterThan(0, $localobject->update($user), $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		$this->assertSame('210000000003139471430009017', $localobject->payment_reference);
	}

	/**
	 * testFactureFournisseurRecGetNextDate
	 *
	 * @return	void
	 */
	public function testFactureFournisseurRecGetNextDate()
	{
		global $db;

		$savtz = date_default_timezone_get();
		date_default_timezone_set('Europe/Paris');

		try {
			$localobject = new FactureFournisseurRec($db);
			$localobject->frequency = 1;
			$localobject->unit_frequency = 'm';

			// Midnight of the 1st after a month of 30 days is the last day of the previous month in UTC
			foreach (array('2026-10-01' => '2026-11-01', '2026-12-01' => '2027-01-01', '2026-03-01' => '2026-04-01') as $day => $expected) {
				$localobject->date_when = dol_stringtotime($day.' 00:00:00', 0);
				$result = dol_print_date($localobject->getNextDate(), '%Y-%m-%d %H:%M:%S', 'tzserver');
				print __METHOD__." day=".$day." result=".$result."\n";
				$this->assertEquals($expected.' 00:00:00', $result);
			}
		} finally {
			date_default_timezone_set($savtz);
		}
	}

	/**
	 * A payment in the invoice currency that settles its whole remaining amount closes the supplier invoice,
	 * even when converting that amount gives one cent less than the sum of the converted lines.
	 *
	 * @return	void
	 */
	public function testFactureFournisseurPaidInInvoiceCurrencyIsClosed()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$rate = 1.0862009;
		$unitpriceininvoicecurrency = 100.10;

		$invoice = new FactureFournisseur($db);
		$invoice->initAsSpecimen();
		$invoice->lines = array();
		$invoice->ref_supplier = 'PAIDININVOICECURRENCY'.dol_now();
		$invoice->multicurrency_code = 'USD';
		$invoice->multicurrency_tx = $rate;
		$this->assertLessThan($invoice->create($user), 0, 'FactureFournisseur::create failed: '.$invoice->error);
		for ($i = 0; $i < 2; $i++) {
			$result = $invoice->addline('Line in invoice currency', 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 'HT', 0, -1, 0, array(), null, 0, $unitpriceininvoicecurrency);
			$this->assertLessThan($result, 0, 'FactureFournisseur::addline failed: '.$invoice->error);
		}
		$this->assertLessThan($invoice->validate($user), 0, 'FactureFournisseur::validate failed: '.$invoice->error);
		$invoice->fetch($invoice->id);

		$amountininvoicecurrency = (float) price2num($invoice->multicurrency_total_ttc, 'MT');
		$this->assertNotEquals((float) price2num($amountininvoicecurrency / $rate, 'MT'), (float) price2num($invoice->total_ttc, 'MT'), 'Test needs lines whose converted sum differs from the converted total');

		$payment = new PaiementFourn($db);
		$payment->datepaye = dol_now();
		$payment->multicurrency_amounts = array($invoice->id => $amountininvoicecurrency);
		$payment->multicurrency_code = array($invoice->id => 'USD');
		$payment->multicurrency_tx = array($invoice->id => $rate);
		$payment->paiementcode = 'LIQ';
		$payment->paiementid = dol_getIdFromCode($db, $payment->paiementcode, 'c_paiement', 'code', 'id', 1);
		$paymentid = $payment->create($user, 1);
		$this->assertLessThan($paymentid, 0, 'PaiementFourn::create failed: '.$payment->error);

		$invoice->fetch($invoice->id);
		$this->assertEquals((float) price2num($invoice->total_ttc, 'MT'), (float) price2num($payment->amount, 'MT'), 'Payment amount in company currency differs from the invoice total');
		$this->assertEquals(FactureFournisseur::STATUS_CLOSED, $invoice->status, 'Supplier invoice fully paid in its currency is not closed');
	}

	/**
	 * testFactureFournisseurPaymentWithExchangeDifference
	 *
	 * An invoice of 700 NZD booked 610 in company currency is paid from a GBP account: the payment settles the 700 NZD,
	 * 650 GBP are debited, worth 600 at the rate of the day, so 10 of exchange gain. Paid from a company currency
	 * account with 620 debited, it is a loss of 10.
	 *
	 * @return	void
	 */
	public function testFactureFournisseurPaymentWithExchangeDifference()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->ACCOUNTING_BOOK_EXCHANGE_DIFFERENCE = 1;
		$this->setRateOfTheDay('NZD', 700 / 610);
		$this->setRateOfTheDay('GBP', 650 / 600);
		$accountgbp = $this->createBankAccount('GBP');

		$invoice = $this->createInvoiceInCurrency('NZD', 700, 700 / 610);
		$payment = $this->createPayment($invoice, 700, $accountgbp);
		$this->assertLessThan(0, $payment->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $accountgbp, '', ''), 'The amount debited is required from an account in another currency than the invoice');

		$payment->bank_amount = 650;
		$banklineid = $payment->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $accountgbp, '', '');
		$this->assertGreaterThan(0, $banklineid, (string) $payment->error);

		$resql = $db->query("SELECT amount, amount_main_currency FROM ".MAIN_DB_PREFIX."bank WHERE rowid = ".((int) $banklineid));
		$obj = $db->fetch_object($resql);
		$this->assertEquals(-650, (float) $obj->amount, 'The GBP account is debited of the amount typed');
		$this->assertEquals(-600, (float) $obj->amount_main_currency, 'The amount debited is converted at the rate of the day');
		$bankline = new AccountLine($db);
		$bankline->id = $banklineid;
		$this->assertEquals(10, $bankline->getExchangeDifference());
		$invoice->fetch($invoice->id);
		$this->assertEquals(FactureFournisseur::STATUS_CLOSED, $invoice->status, 'The payment in the invoice currency settles it');

		$accountcompany = $this->createBankAccount($conf->currency);
		$invoice = $this->createInvoiceInCurrency('NZD', 700, 700 / 610);
		$payment = $this->createPayment($invoice, 700, $accountcompany);
		$payment->bank_amount = 620;
		$banklineid = $payment->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $accountcompany, '', '');
		$this->assertGreaterThan(0, $banklineid, (string) $payment->error);
		$resql = $db->query("SELECT amount, amount_main_currency FROM ".MAIN_DB_PREFIX."bank WHERE rowid = ".((int) $banklineid));
		$obj = $db->fetch_object($resql);
		$this->assertEquals(-620, (float) $obj->amount);
		$this->assertNull($obj->amount_main_currency);
		$bankline->id = $banklineid;
		$this->assertEquals(-10, $bankline->getExchangeDifference());

		$conf->global->ACCOUNTING_BOOK_EXCHANGE_DIFFERENCE = 0;
	}

	/**
	 * testFactureFournisseurPaymentWithoutExchangeDifference
	 *
	 * Without the option, an invoice can not be paid from an account in a third currency and the bank gets the invoice at its rate.
	 *
	 * @return	void
	 */
	public function testFactureFournisseurPaymentWithoutExchangeDifference()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->ACCOUNTING_BOOK_EXCHANGE_DIFFERENCE = 0;
		$this->setRateOfTheDay('NZD', 700 / 610);
		$this->setRateOfTheDay('GBP', 650 / 600);
		$accountgbp = $this->createBankAccount('GBP');
		$invoice = $this->createInvoiceInCurrency('NZD', 700, 700 / 610);

		$payment = new PaiementFourn($db);
		$payment->datepaye = dol_now();
		$payment->multicurrency_amounts = array($invoice->id => 700);
		$payment->paiementid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$payment->fk_account = $accountgbp;
		$this->assertLessThan(0, $payment->create($user, 1), 'An invoice in NZD can not be paid from a GBP account');

		$accountcompany = $this->createBankAccount($conf->currency);
		$payment = $this->createPayment($invoice, 700, $accountcompany);
		$payment->bank_amount = 620;
		$banklineid = $payment->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $accountcompany, '', '');
		$this->assertGreaterThan(0, $banklineid, (string) $payment->error);
		$resql = $db->query("SELECT amount FROM ".MAIN_DB_PREFIX."bank WHERE rowid = ".((int) $banklineid));
		$this->assertEquals(-610, (float) $db->fetch_object($resql)->amount, 'The amount typed is ignored');
		$bankline = new AccountLine($db);
		$bankline->id = $banklineid;
		$this->assertEquals(0, $bankline->getExchangeDifference());
	}

	/**
	 * Create and validate a supplier invoice of one line in a currency
	 *
	 * @param	string	$currency	Currency code
	 * @param	float	$amount		Amount in the currency
	 * @param	float	$rate		Rate of the invoice
	 * @return	FactureFournisseur
	 */
	private function createInvoiceInCurrency(string $currency, float $amount, float $rate): FactureFournisseur
	{
		global $user, $db;

		$invoice = new FactureFournisseur($db);
		$invoice->initAsSpecimen();
		$invoice->lines = array();
		$invoice->ref_supplier = 'XDIFF'.uniqid();
		$invoice->multicurrency_code = $currency;
		$invoice->multicurrency_tx = $rate;
		$this->assertGreaterThan(0, $invoice->create($user), (string) $invoice->error);
		$this->assertGreaterThan(0, $invoice->addline('Line in invoice currency', 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 'HT', 0, -1, 0, array(), null, 0, $amount), (string) $invoice->error);
		$this->assertGreaterThan(0, $invoice->validate($user), (string) $invoice->error);
		$invoice->fetch($invoice->id);

		return $invoice;
	}

	/**
	 * Create a payment of a supplier invoice in its currency
	 *
	 * @param	FactureFournisseur	$invoice	Invoice
	 * @param	float				$amount		Amount in the currency of the invoice
	 * @param	int					$accountid	Id of the bank account
	 * @return	PaiementFourn
	 */
	private function createPayment(FactureFournisseur $invoice, float $amount, int $accountid): PaiementFourn
	{
		global $user, $db;

		$payment = new PaiementFourn($db);
		$payment->datepaye = dol_now();
		$payment->multicurrency_amounts = array($invoice->id => $amount);
		$payment->paiementid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$payment->fk_account = $accountid;
		$this->assertGreaterThan(0, $payment->create($user, 1), (string) $payment->error);

		return $payment;
	}

	/**
	 * Set the rate of a currency for today
	 *
	 * @param	string	$code	Currency code
	 * @param	float	$rate	Rate
	 * @return	void
	 */
	private function setRateOfTheDay(string $code, float $rate): void
	{
		global $user, $db;

		$id = MultiCurrency::getIdFromCode($db, $code);
		if ($id <= 0) {
			$currency = new MultiCurrency($db);
			$currency->code = $code;
			$currency->name = $code;
			$id = $currency->create($user);
			$this->assertGreaterThan(0, $id, $currency->errorsToString());
		}
		$currencyrate = new CurrencyRate($db);
		$currencyrate->rate = $rate;
		$this->assertGreaterThan(0, $currencyrate->create($user, $id));
	}

	/**
	 * Create a bank account in a currency
	 *
	 * @param	string	$currency	Currency code
	 * @return	int					Id of the bank account
	 */
	private function createBankAccount(string $currency): int
	{
		global $user, $db;

		$account = new Account($db);
		$account->ref = 'XD'.$currency.substr(uniqid(), -6);
		$account->label = $account->ref;
		$account->type = Account::TYPE_CURRENT;
		$account->country_id = 1;
		$account->date_solde = dol_now();
		$account->currency_code = $currency;
		$id = $account->create($user);
		$this->assertGreaterThan(0, $id, $account->errorsToString());

		return $id;
	}
}
