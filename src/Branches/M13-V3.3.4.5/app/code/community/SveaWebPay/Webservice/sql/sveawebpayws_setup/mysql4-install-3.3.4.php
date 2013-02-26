<?php

/**
 * SveaWebPay Payment Module for Magento.
 *   Copyright (C) 2012  SveaWebPay
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Any questions may be directed to kundtjanst.sveawebpay@sveaekonomi.se
 */
 
$this->startSetup();
$this->run("

DROP TABLE IF EXISTS {$this->getTable('sveawebpay_webservice_paymentplan')};
CREATE TABLE IF NOT EXISTS `{$this->getTable('sveawebpay_webservice_paymentplan')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paymentplan_number` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` float NOT NULL,
  `contract_number` BIGINT(20),
  PRIMARY KEY (`id`),
  UNIQUE KEY `svea_order_id` (`paymentplan_number`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('sveawebpay_webservice_order')};
CREATE TABLE IF NOT EXISTS `{$this->getTable('sveawebpay_webservice_order')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `svea_order_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `svea_order_id` (`svea_order_id`,`order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('sveawebpay_webservice_invoice')};
CREATE TABLE IF NOT EXISTS `{$this->getTable('sveawebpay_webservice_invoice')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` float NOT NULL,
  `number` text NOT NULL,
  `date` text NOT NULL,
  `due_date` text NOT NULL,
  `pdflink` text,
  `sveawebpay_webservice_order_id` int(11) NOT NULL,
  `magento_invoice_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('sveawebpay_webservice_refund')};
CREATE TABLE IF NOT EXISTS `{$this->getTable('sveawebpay_webservice_refund')}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` float NOT NULL,
  `number` text NOT NULL,
  `date` int(11) NOT NULL,
  `due_date` text NOT NULL,
  `pdflink` text NOT NULL,
  `sveawebpay_webservice_invoice_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('sveawebpay_webservice_campains')};
CREATE TABLE  IF NOT EXISTS `{$this->getTable('sveawebpay_webservice_campains')}` (
		`campaincode` VARCHAR( 100 ) NOT NULL ,
		`description` VARCHAR( 100 ) NOT NULL ,
		`paymentplantype` VARCHAR( 100 ) NOT NULL ,
		`contractlength` INT NOT NULL ,
		`monthlyannuityfactor` DOUBLE NOT NULL ,
		`initialfee` DOUBLE NOT NULL ,
		`notificationfee` DOUBLE NOT NULL ,
		`interestratepercentage` INT NOT NULL ,
		`interestFreeMonths` INT NOT NULL ,
		`paymentFreeMonths` INT NOT NULL ,
		`fromamount` DOUBLE NOT NULL ,
		`toamount` DOUBLE NOT NULL ,
		`timestamp` INT NOT NULL ,
        `storeid` INT NOT NULL ,
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

");

$this->endSetup();