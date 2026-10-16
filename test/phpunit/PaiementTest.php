<?php
/* Copyright (C) 2010       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/PaiementTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the customer payment (Paiement) class.
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */


//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
global $conf, $user, $langs, $db;
require_once dirname(__FILE__) . '/../../htdocs/master.inc.php';
/**
 * @var DoliDB $db
 * @var Conf $conf
 * @var Translate $langs
 * @var User $user
 */
require_once dirname(__FILE__) . '/../../htdocs/compta/paiement/class/paiement.class.php';
require_once dirname(__FILE__) . '/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__) . '/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__) . '/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests on the Paiement (customer payment) class.
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PaiementTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('invoice'), " module customer invoice must be enabled");
		parent::setUpBeforeClass();
	}

	/**
	 * A payment in the invoice currency that settles its whole remaining amount closes the invoice,
	 * even when converting that amount gives one cent less than the sum of the converted lines.
	 *
	 * @return	void
	 */
	public function testPaiementCreateInInvoiceCurrencyClosesInvoice()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->FAC_FORCE_DATE_VALIDATION = 0;
		$conf->global->INVOICE_CHECK_POSTERIOR_DATE = 0;

		$rate = 1.0862009;
		$unitpriceininvoicecurrency = 100.10;

		$invoice = new Facture($db);
		$invoice->initAsSpecimen('nolines');
		$invoice->multicurrency_code = 'USD';
		$invoice->multicurrency_tx = $rate;
		$this->assertLessThan($invoice->create($user), 0, 'Facture::create failed: '.$invoice->error);
		for ($i = 0; $i < 2; $i++) {
			$result = $invoice->addline('Line in invoice currency', 0, 1, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT', 0, Facture::TYPE_STANDARD, -1, 0, '', 0, 0, null, 0, '', array(), 100, 0, null, $unitpriceininvoicecurrency);
			$this->assertLessThan($result, 0, 'Facture::addline failed: '.$invoice->error);
		}
		$this->assertLessThan($invoice->validate($user), 0, 'Facture::validate failed: '.$invoice->error);
		$invoice->fetch($invoice->id);

		$amountininvoicecurrency = (float) price2num($invoice->multicurrency_total_ttc, 'MT');
		$this->assertNotEquals((float) price2num($amountininvoicecurrency / $rate, 'MT'), (float) price2num($invoice->total_ttc, 'MT'), 'Test needs lines whose converted sum differs from the converted total');

		$payment = new Paiement($db);
		$payment->datepaye = dol_now();
		$payment->multicurrency_amounts = array($invoice->id => $amountininvoicecurrency);
		$payment->multicurrency_code = array($invoice->id => 'USD');
		$payment->multicurrency_tx = array($invoice->id => $rate);
		$payment->paiementcode = 'LIQ';
		$payment->paiementid = dol_getIdFromCode($db, $payment->paiementcode, 'c_paiement', 'code', 'id', 1);
		$paymentid = $payment->create($user, 1);
		$this->assertLessThan($paymentid, 0, 'Paiement::create failed: '.$payment->error);

		$invoice->fetch($invoice->id);
		$this->assertEquals((float) price2num($invoice->total_ttc, 'MT'), (float) price2num($payment->amount, 'MT'), 'Payment amount in company currency differs from the invoice total');
		$this->assertEquals(Facture::STATUS_CLOSED, $invoice->status, 'Invoice fully paid in its currency is not closed');
	}
}
