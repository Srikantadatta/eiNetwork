<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';
require_once 'services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class DBMaintenance extends Admin {
	function launch() 	{
		global $configArray;
		global $interface;
		mysql_select_db($configArray['Database']['database_vufind_dbname']);
							
		//Create updates table if one doesn't exist already
		$this->createUpdatesTable();

		$availableUpdates = $this->getSQLUpdates();

		if (isset($_REQUEST['submit'])){
			$interface->assign('showStatus', true);

			//Process the updates
			foreach ($availableUpdates as $key => $update){
				if (isset($_REQUEST["selected"][$key])){
					$sqlStatements = $update['sql'];
					$updateOk = true;
					foreach ($sqlStatements as $sql){
						//Give enough time for long queries to run
						set_time_limit(120);
						if (method_exists($this, $sql)){
							$this->$sql();
						}else{
							$result = mysql_query($sql);
							if ($result == 0 || $result == false){
								if (isset($update['continueOnError']) && $update['continueOnError']){
									if (!isset($update['status'])) $update['status'] = '';
									$update['status'] .= 'Warning: ' . mysql_error() . "<br/>";
								}else{
									$update['status'] = 'Update failed ' . mysql_error();
									$updateOk = false;
									break;
								}
							}else{
								$update['status'] = 'Update succeeded';
							}
							
						}
					}
					if ($updateOk){
						$this->markUpdateAsRun($key);
					}
					$availableUpdates[$key] = $update;
				}
			}
		}

		//Check to see which updates have already been performed.
		$availableUpdates = $this->checkWhichUpdatesHaveRun($availableUpdates);

		$interface->assign('sqlUpdates', $availableUpdates);

		$interface->setTemplate('dbMaintenance.tpl');
		$interface->setPageTitle('Database Maintenance');
		$interface->display('layout.tpl');

	}

	private function getSQLUpdates() {
		global $configArray;
		
		return array(
			'roles_1' => array(
				'title' => 'Roles 1',
				'description' => 'Add new role for epubAdmin',
				'dependencies' => array(),
				'sql' => array(
					"INSERT INTO roles (name, description) VALUES ('epubAdmin', 'Allows administration of eContent.')",
				),
			),
			'library_1' => array(
				'title' => 'Library 1',
				'description' => 'Update Library table to include showSeriesAsTab column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN showSeriesAsTab TINYINT NOT NULL DEFAULT '0';",
					"UPDATE library SET showSeriesAsTab = '1' where subdomain in ('adams') ",
				),
			),
			'library_2' => array(
				'title' => 'Library 2',
				'description' => 'Update Library table to include showItsHere column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN showItsHere TINYINT NOT NULL DEFAULT '1';",
					"UPDATE library SET showItsHere = '0' where subdomain in ('adams', 'msc') ",
				),
			),
			'library_3' => array(
				'title' => 'Library 3',
				'description' => 'Update Library table to include holdDisclaimer column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN holdDisclaimer TEXT;",
					"UPDATE library SET holdDisclaimer = 'I understand that by requesting this item, information from my library patron record, including my contact information may be made available to the lending library.' where subdomain in ('msc') ",
				),
			),
			'library_5' => array(
				'title' => 'Library 5',
				'description' => 'Set up a link to boopsie in mobile',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `library` ADD `boopsieLink` VARCHAR(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;",
				),
			),
			'library_6' => array(
				'title' => 'Library 6',
				'description' => 'Add fields orginally defined for Marmot',
				'dependencies' => array(),
				'continueOnError' => true,
				'sql' => array(
			
					"ALTER TABLE `library` ADD `showHoldCancelDate` TINYINT(4) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `enablePospectorIntegration` TINYINT(4) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `prospectorCode` VARCHAR(10) NOT NULL DEFAULT '';",
					"ALTER TABLE `library` ADD `showRatings` TINYINT(4) NOT NULL DEFAULT '1';",
					"ALTER TABLE `library` ADD `searchesFile` VARCHAR(15) NOT NULL DEFAULT 'default';",
					"ALTER TABLE `library` ADD `minimumFineAmount` FLOAT NOT NULL DEFAULT '0';",
					"UPDATE library set minimumFineAmount = '5' where showEcommerceLink = '1'",
					"ALTER TABLE `library` ADD `enableGenealogy` TINYINT(4) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `enableCourseReserves` TINYINT(1) NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `exportOptions` VARCHAR(100) NOT NULL DEFAULT 'RefWorks|EndNote';",
					"ALTER TABLE `library` ADD `enableSelfRegistration` TINYINT NOT NULL DEFAULT '0';",
					"ALTER TABLE `library` ADD `useHomeLinkInBreadcrumbs` TINYINT(4) NOT NULL DEFAULT '0';",
				),
			),
		
      'user_display_name' => array(
        'title' => 'User display name',
        'description' => 'Add displayName field to User table to allow users to have aliases',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE user ADD displayName VARCHAR( 30 ) NOT NULL DEFAULT ''",
		),
		),
		
		'user_phone' => array(
        'title' => 'User phone',
        'description' => 'Add phone field to User table to allow phone numbers to be displayed for Materials Requests',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE user ADD phone VARCHAR( 30 ) NOT NULL DEFAULT ''",
		),
		),
		
		'user_ilsType' => array(
        'title' => 'User Type',
        'description' => 'Add patronType field to User table to allow for functionality to be controlled based on the type of patron within the ils',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE user ADD patronType VARCHAR( 30 ) NOT NULL DEFAULT ''",
		),
		),
    	 
      'list_widgets' => array(
        'title' => 'Setup Configurable List Widgets',
        'description' => 'Create tables related to configurable list widgets',
        'dependencies' => array(),
        'sql' => array(
          "DROP TABLE IF EXISTS list_widgets;",
          "DROP TABLE IF EXISTS list_widget_lists;",
          "CREATE TABLE IF NOT EXISTS list_widgets (".
            "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
            "`name` VARCHAR(50) NOT NULL, " . 
            "`description` TEXT, " .
            "`showTitleDescriptions` TINYINT DEFAULT 1, " .
            "`onSelectCallback` VARCHAR(255) DEFAULT '' " .
          ") ENGINE = MYISAM COMMENT = 'A widget that can be displayed within VuFind or within other sites' ",
          "CREATE TABLE IF NOT EXISTS list_widget_lists (".
            "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
            "`listWidgetId` INT NOT NULL, " .
            "`weight` INT NOT NULL DEFAULT 0, " .
            "`displayFor` ENUM('all', 'loggedIn', 'notLoggedIn') NOT NULL DEFAULT 'all', " .
            "`name` VARCHAR(50) NOT NULL, " .
            "`source` VARCHAR(500) NOT NULL, " . 
            "`fullListLink` VARCHAR(500) DEFAULT '' " .
          ") ENGINE = MYISAM COMMENT = 'The lists that should appear within the widget' ",
    	     ),
    	     ),
      
      'list_widgets_home' => array(
        'title' => 'List Widget Home',
        'description' => 'Create the default homepage widget',
        'dependencies' => array(),
        'sql' => array(
					"INSERT INTO list_widgets (name, description, showTitleDescriptions, onSelectCallback) VALUES ('home', 'Default example widget.', '1','')",
					"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor, fullListLink) VALUES ('1', '1', 'highestRated', 'Highest Rated', 'all', '')",
					"INSERT INTO list_widget_lists (listWidgetId, weight, source, name, displayFor, fullListLink) VALUES ('1', '2', 'recentlyReviewed', 'Recently Reviewed', 'all', '')",
				),
			),

      'list_wdiget_list_update_1' => array(
        'title' => 'List Widget List Source Length Update',
        'description' => 'Update length of source field to accommodate search source type',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `list_widget_lists` CHANGE `source` `source` VARCHAR( 500 ) NOT NULL "
        ),
      ),
			
      'index_search_stats' => array(
        'title' => 'Index search stats table',
        'description' => 'Add index to search stats table to improve autocomplete speed',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `search_stats` ADD INDEX `search_index` ( `type` , `libraryId` , `locationId` , `phrase`, `numResults` )",
        ),
      ),
      'list_wdiget_update_1' => array(
        'title' => 'Update List Widget 1',
        'description' => 'Update List Widget to allow custom css files to be included and allow lists do be displayed in dropdown rather than tabs',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `list_widgets` ADD COLUMN `customCss` VARCHAR( 500 ) NOT NULL ",
          "ALTER TABLE `list_widgets` ADD COLUMN `listDisplayType` ENUM('tabs', 'dropdown') NOT NULL DEFAULT 'tabs'"
        ),
      ),
      'library_4' => array(
				'title' => 'Library 4',
				'description' => 'Update Library table to include enableAlphaBrowse column',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE library ADD COLUMN enableAlphaBrowse TINYINT DEFAULT '1';",
				),
			),
			
			'genealogy_1' => array(
				'title' => 'Genealogy Update 1',
				'description' => 'Update Genealogy 1 for Steamboat Springs to add cemetery information.',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE person ADD COLUMN veteranOf VARCHAR(100) NULL DEFAULT ''",
					"ALTER TABLE person ADD COLUMN addition VARCHAR(100) NULL DEFAULT ''",
					"ALTER TABLE person ADD COLUMN block VARCHAR(100) NULL DEFAULT ''",
					"ALTER TABLE person ADD COLUMN lot INT(11) NULL",
					"ALTER TABLE person ADD COLUMN grave INT(11) NULL",
					"ALTER TABLE person ADD COLUMN tombstoneInscription TEXT",
					"ALTER TABLE person ADD COLUMN addedBy INT(11) NOT NULL DEFAULT -1",
					"ALTER TABLE person ADD COLUMN dateAdded INT(11) NULL",
					"ALTER TABLE person ADD COLUMN modifiedBy INT(11) NOT NULL DEFAULT -1",
					"ALTER TABLE person ADD COLUMN lastModified INT(11) NULL",
					"ALTER TABLE person ADD COLUMN privateComments TEXT",
					"ALTER TABLE person ADD COLUMN importedFrom VARCHAR(50) NULL",
				),
			),
      
      'recommendations_optOut' => array(
        'title' => 'Recommendations Opt Out',
        'description' => 'Add tracking for whether the user wants to opt out of recommendations',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `user` ADD `disableRecommendations` TINYINT NOT NULL DEFAULT '0'",
    	     ),
    	     ),

      'editorial_review' => array(
        'title' => 'Create Editorial Review table',
        'description' => 'Create table to store editorial reviews from external reviews, i.e. book-a-day blog',
        'dependencies' => array(),
        'sql' => array(
          "DROP TABLE IF EXISTS editorial_reviews",
          "CREATE TABLE editorial_reviews (".
            "editorialReviewId int NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
            "recordId VARCHAR(50) NOT NULL, ".
            "title VARCHAR(255) NOT NULL, ".
            "pubDate BIGINT NOT NULL, ".
            "review TEXT, ".
            "source VARCHAR(50) NOT NULL".
          ")",
    	     ),
    	     ),
			'purchase_link_tracking' => array(
        'title' => 'Create Purchase Link Tracking Table',
        'description' => 'Create table to track data about the Purchase Links that were clicked',
        'dependencies' => array(),
        'sql' => array(
          "DROP TABLE IF EXISTS purchaseLinkTracking",

				  'CREATE TABLE IF NOT EXISTS purchaseLinkTracking (' .
				  'purchaseLinkId int(11) NOT NULL AUTO_INCREMENT, '.
				  'ipAddress varchar(30) NULL, '.
          'recordId VARCHAR(50) NOT NULL, '.
          'store VARCHAR(255) NOT NULL, '.
				  'trackingDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, '.
				  'PRIMARY KEY (purchaseLinkId) '.
					') ENGINE=InnoDB',

           'ALTER TABLE purchaseLinkTracking ADD INDEX ( `purchaseLinkId` )',
    	     ),
			),
      'usage_tracking' => array(
        'title' => 'Create Usage Tracking Table',
        'description' => 'Create table to track aggregate page view data',
        'dependencies' => array(),
        'sql' => array(
          "DROP TABLE IF EXISTS usageTracking",

				  'CREATE TABLE IF NOT EXISTS usageTracking (' .
				  'usageId int(11) NOT NULL AUTO_INCREMENT, '.
				  'ipId INT NOT NULL, ' .
					'locationId INT NOT NULL, ' .
					'numPageViews INT NOT NULL DEFAULT "0", ' .
					'numHolds INT NOT NULL DEFAULT "0", ' .
					'numRenewals INT NOT NULL DEFAULT "0", ' .
          "trackingDate BIGINT NOT NULL, ".
				  'PRIMARY KEY (usageId) '.
					') ENGINE=InnoDB',

           'ALTER TABLE usageTracking ADD INDEX ( `usageId` )',
				),
			),		
			
			'resource_update_table' => array(
				'title' => 'Update resource table',
				'description' => 'Update resource tracking table to include additional information resources for sorting',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE resource ADD author VARCHAR(255)',
					'ALTER TABLE resource ADD title_sort VARCHAR(255)',
					'ALTER TABLE resource ADD isbn VARCHAR(13)',
					'ALTER TABLE resource ADD upc VARCHAR(13)', //Have to use 13 since some publishers use the ISBN as the UPC.
					'ALTER TABLE resource ADD format VARCHAR(50)',
					'ALTER TABLE resource ADD format_category VARCHAR(50)',
					'ALTER TABLE `resource` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
				),
			),
			
			'resource_update_table_2' => array(
				'title' => 'Update resource table 2',
				'description' => 'Update resource tracking table to make sure that title and author are utf8 encoded',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''",
					"ALTER TABLE `resource` CHANGE `source` `source` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'VuFind'",
					"ALTER TABLE `resource` CHANGE `author` `author` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci",
					"ALTER TABLE `resource` CHANGE `title_sort` `title_sort` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci",
				),
			),
			
			'resource_update3' => array(
				'title' => 'Update resource table 3',
				'description' => 'Update resource table to include the checksum of the marc record so we can skip updating records that haven\'t changed',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` ADD marc_checksum BIGINT",
					"ALTER TABLE `resource` ADD date_updated INT(11)",
				),
			),
			
			'resource_update4' => array(
				'title' => 'Update resource table 4',
				'description' => 'Update resource table to include a field for the actual marc record',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` ADD marc BLOB",
				),
			),
			
			'resource_update5' => array(
				'title' => 'Update resource table 5',
				'description' => 'Add a short id column for use with certain ILS i.e. Millennium',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `resource` ADD shortId VARCHAR(20)",
					"ALTER TABLE `resource` ADD INDEX (shortId)", 
				),
			),
			
			'resource_callnumber' => array(
				'title' => 'Resource call numbers',
				'description' => 'Create table to store call numbers for resources',
				'dependencies' => array(),
				'sql' => array(
					"DROP TABLE IF EXISTS resource_callnumber",

				  'CREATE TABLE IF NOT EXISTS resource_callnumber (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'resourceId INT NOT NULL, ' .
					'locationId INT NOT NULL, ' .
					'callnumber VARCHAR(50) NOT NULL DEFAULT "", ' .
					'PRIMARY KEY (id), '.
					'INDEX (`callnumber`), ' .
					'INDEX (`resourceId`), ' .
					'INDEX (`locationId`)' .
					') ENGINE=InnoDB',
				),
			),
			
			'resource_subject' => array(
				'title' => 'Resource subject',
				'description' => 'Create table to store subjects for resources',
				'dependencies' => array(),
				'sql' => array(
					"DROP TABLE IF EXISTS subject",
			
					'CREATE TABLE IF NOT EXISTS subject (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'subject VARCHAR(100) NOT NULL, ' .
					'PRIMARY KEY (id), '.
					'INDEX (`subject`)' .
					') ENGINE=InnoDB',
			
					"DROP TABLE IF EXISTS resource_subject",

				  'CREATE TABLE IF NOT EXISTS resource_subject (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'resourceId INT(11) NOT NULL, ' .
					'subjectId INT(11) NOT NULL, ' .
					'PRIMARY KEY (id), '.
					'INDEX (`resourceId`), ' .
					'INDEX (`subjectId`)' .
					') ENGINE=InnoDB',
				),
			),

			'readingHistory' => array(
        'title' => 'Reading History Creation',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
			    'DROP TABLE IF EXISTS user_reading_history;',
			    "CREATE TABLE IF NOT EXISTS  user_reading_history(" .
				    "`userId` INT NOT NULL COMMENT 'The id of the user who checked out the item', " .
						"`resourceId` INT NOT NULL COMMENT 'The record id of the item that was checked out', " .
						"`lastCheckoutDate` DATE NOT NULL COMMENT 'The first day we detected that the item was checked out to the patron', " .
						"`firstCheckoutDate` DATE NOT NULL COMMENT 'The last day we detected the item was checked out to the patron', " .
						"`daysCheckedOut` INT NOT NULL COMMENT 'The total number of days the item was checked out even if it was checked out multiple times.', " .
						"PRIMARY KEY ( `userId` , `resourceId` )" .
					") ENGINE = MYISAM COMMENT = 'The reading history for patrons';",
	      ),
			),
			
      'coverArt_suppress' => array(
        'title' => 'Cover Art Suppress',
        'description' => 'Add tracking for whether the user wants to suppress cover art',
        'dependencies' => array(),
        'sql' => array(
          "ALTER TABLE `user` ADD `disableCoverArt` TINYINT NOT NULL DEFAULT '0'",
        ),
      ),
      
      'externalLinkTracking' => array(
        'title' => 'Create External Link Tracking Table',
        'description' => 'Create table to track links to external sites from 856 tags or eContent',
        'dependencies' => array(),
        'sql' => array(
          "DROP TABLE IF EXISTS externalLinkTracking",

				  'CREATE TABLE IF NOT EXISTS externalLinkTracking (' .
				  'externalLinkId int(11) NOT NULL AUTO_INCREMENT, '.
				  'ipAddress varchar(30) NULL, '.
          'recordId varchar(50) NOT NULL, '.
          'linkUrl varchar(400) NOT NULL, '.
          'linkHost varchar(200) NOT NULL, '.
				  'trackingDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, '.
				  'PRIMARY KEY (externalLinkId) '.
					') ENGINE=InnoDB',
	      ),
			),
			
			'readingHistoryUpdate1' => array(
        'title' => 'Reading History Update 1',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
			    'ALTER TABLE `user_reading_history` DROP PRIMARY KEY',
			    'ALTER TABLE `user_reading_history` ADD UNIQUE `user_resource` ( `userId` , `resourceId` ) ',
          'ALTER TABLE `user_reading_history` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ',
	      ),
			),
			
			'materialsRequest' => array(
        'title' => 'Materials Request Table Creation',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
			    "DROP TABLE IF EXISTS materials_request",

				  'CREATE TABLE IF NOT EXISTS materials_request (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'title varchar(255), '.
          'author varchar(255), '.
          'format varchar(25), '.
          'ageLevel varchar(25), '.
				  'isbn_upc varchar(15), '.
				  'oclcNumber varchar(30), '.
				  'publisher varchar(255), '.
				  'publicationYear varchar(4), '.
				  'articleInfo varchar(255), '.
				  'abridged TINYINT, '.
				  'about TEXT, '.
				  'comments TEXT, '.
				  "status enum('pending', 'owned', 'purchased', 'referredToILL', 'ILLplaced', 'ILLreturned', 'notEnoughInfo', 'notAcquiredOutOfPrint', 'notAcquiredNotAvailable', 'notAcquiredFormatNotAvailable', 'notAcquiredPrice', 'notAcquiredPublicationDate', 'requestCancelled') DEFAULT 'pending', ".
				  'dateCreated int(11), '.
				  'createdBy int(11), ' .
				  'dateUpdated int(11), '.
				  'PRIMARY KEY (id) '.
					') ENGINE=InnoDB',
	      ),
			),
			
			'materialsRequest_update1' => array(
				'title' => 'Materials Request Update 1',
				'description' => 'Material Request add fields for sending emails and creating holds',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `emailSent` TINYINT NOT NULL DEFAULT 0',
					'ALTER TABLE `materials_request` ADD `holdsCreated` TINYINT NOT NULL DEFAULT 0',
				),
			),
			
			'materialsRequest_update2' => array(
				'title' => 'Materials Request Update 2',
				'description' => 'Material Request add fields phone and email so user can supply a different email address',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `email` VARCHAR(80)',
					'ALTER TABLE `materials_request` ADD `phone` VARCHAR(15)',
				),
			),
			
			'materialsRequest_update3' => array(
				'title' => 'Materials Request Update 3',
				'description' => 'Material Request add fields season, magazineTitle, split isbn and upc',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `season` VARCHAR(80)',
					'ALTER TABLE `materials_request` ADD `magazineTitle` VARCHAR(255)',
					'ALTER TABLE `materials_request` CHANGE `isbn_upc` `isbn` VARCHAR( 15 )',
					'ALTER TABLE `materials_request` ADD `upc` VARCHAR(15)',
					'ALTER TABLE `materials_request` ADD `issn` VARCHAR(8)',
					'ALTER TABLE `materials_request` ADD `bookType` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `subFormat` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `magazineDate` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `magazineVolume` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `magazinePageNumbers` VARCHAR(20)',
					'ALTER TABLE `materials_request` ADD `placeHoldWhenAvailable` TINYINT',
					'ALTER TABLE `materials_request` ADD `holdPickupLocation` VARCHAR(10)',
					'ALTER TABLE `materials_request` ADD `bookmobileStop` VARCHAR(50)',
				),
			),
			
			'materialsRequest_update4' => array(
				'title' => 'Materials Request Update 4',
				'description' => 'Material Request add illItem field and make status field not an enum so libraries can easily add statuses',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `illItem` VARCHAR(80)',
				),
			),
			
			'materialsRequest_update5' => array(
				'title' => 'Materials Request Update 5',
				'description' => 'Material Request add magazine number',
				'dependencies' => array(),
				'sql' => array(
					'ALTER TABLE `materials_request` ADD `magazineNumber` VARCHAR(80)',
				),
			),
			
			'materialsRequestStatus' => array(
        'title' => 'Materials Request Status Table Creation',
        'description' => 'Update reading History to include an id table',
        'dependencies' => array(),
        'sql' => array(
			    "DROP TABLE IF EXISTS materials_request_status",

				  'CREATE TABLE IF NOT EXISTS materials_request_status (' .
				  'id int(11) NOT NULL AUTO_INCREMENT, '.
				  'description varchar(80), '.
          'isDefault TINYINT DEFAULT 0, '.
				  'sendEmailToPatron TINYINT, '.
          'emailTemplate TEXT, '.
				  'isOpen TINYINT, '.
				  'isPatronCancel TINYINT, '.
				  'PRIMARY KEY (id) '.
					') ENGINE=InnoDB',
			
					"INSERT INTO materials_request_status (description, isDefault, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Pending', 1, 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Already owned/On order', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Item purchased', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - Adult', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - J/YA', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Referred to Collection Development - AV', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('ILL Under Review', 0, '', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Referred to ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Request Filled by ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Ineligible ILL', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Not enough info - please contact Collection Development to clarify', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - out of print', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available in the US', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not available from vendor', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - not published', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - price', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unable to acquire the item - publication date', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Unavailable', 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen, isPatronCancel) VALUES ('Cancelled by Patron', 0, '', 0, 1)",
					"INSERT INTO materials_request_status (description, sendEmailToPatron, emailTemplate, isOpen) VALUES ('Cancelled - Duplicate Request', 0, '', 0)",
			
					"UPDATE materials_request SET status = (SELECT id FROM materials_request_status WHERE isDefault =1)",
			
					"ALTER TABLE materials_request CHANGE `status` `status` INT(11)"
			),
			),
			
			'catalogingRole' => array(
				'title' => 'Create cataloging role',
				'description' => 'Create cataloging role to handle materials requests, econtent loading, etc.',
				'dependencies' => array(),
				'sql' => array(
					"INSERT INTO `roles` (`name`, `description`) VALUES ('cataloging', 'Allows user to perform cataloging activities.')",
				),
			),
			
			'indexUsageTracking' => array(
				'title' => 'Index Usage Tracking',
				'description' => 'Update Usage Tracking to include index based on ip and tracking date',
				'dependencies' => array(),
				'sql' => array(
					"ALTER TABLE `usageTracking` ADD INDEX `IP_DATE` ( `ipId` , `trackingDate` )",
				), 
			),
			
			'utf8_update' => array(
			'title' => 'Update to UTF-8',
			'description' => 'Update database to use UTF-8 encoding',
			'dependencies' => array(),
			'continueOnError' => true,
			'sql' => array(
				"ALTER DATABASE " . $configArray['Database']['database_vufind_dbname'] . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE administrators CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE bad_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE circulationStatus CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE comments CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE db_update CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE editorial_reviews CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE externalLinkTracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE ip_lookup CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE library CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE list_widgets CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE list_widget_lists CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE location CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE nonHoldableLocations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE pTypeRestrictedLocations CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE purchaseLinkTracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE resource CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE resource_tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE search CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE search_stats CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE session CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE spelling_words CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE tags CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE usageTracking CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_list CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_rating CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_reading_history CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_resource CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_roles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				"ALTER TABLE user_suggestions CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
		),
		),
		
		'index_resources' => array(
			'title' => 'Index resources',
			'description' => 'Add a new index to resources table to make record id and source unique',
			'dependencies' => array(),
			'sql' => array(
				//Update resource table indexes
				"ALTER TABLE `resource` ADD UNIQUE `records_by_source` (`record_id`, `source`)" 
			),
		),
		
		'alpha_browse_setup' => array(
			'title' => 'Setup Alphabetic Browse',
			'description' => 'Create tables to handle alphabetic browse functionality.',
			'dependencies' => array(),
			'sql' => array(
				"DROP TABLE IF EXISTS title_browse",
				"DROP TABLE IF EXISTS author_browse",
				"DROP TABLE IF EXISTS callnumber_browse",
				"DROP TABLE IF EXISTS subject_browse",
				"CREATE TABLE `title_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
				"CREATE TABLE `author_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
				"CREATE TABLE `callnumber_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
				"CREATE TABLE `subject_browse` ( 
					`id` INT NOT NULL COMMENT 'The id of the browse record in numerical order based on the sort order of the rows',
					`value` VARCHAR( 255 ) NOT NULL COMMENT 'The original value',
					`numResults` INT NOT NULL COMMENT 'The number of results found in the table',
				PRIMARY KEY ( `id` ) ,
				INDEX ( `value` )
				) ENGINE = InnoDB;",
			),
		),
		
		'indexLog' => array(
      'title' => 'Reindex Log table',
      'description' => 'Create Reindex Log table to track reindexing.',
      'dependencies' => array(),
      'sql' => array(
		    'DROP TABLE IF EXISTS reindex_log;',
		    "CREATE TABLE IF NOT EXISTS reindex_log(" .
			    "`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of reindex log', " .
					"`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
					"`endTime` INT(11) NULL COMMENT 'The timestamp when the reindex process ended', " .
					"`numRecordsAddedToSolr` INT(11), " .
					"`numRecordsRemovedFromSolr` INT(11), " .
					"`numUnchangedRecords` INT(11), " .
					"`notes` LONGTEXT COMMENT 'Detailed information about the reindex process.', " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = MYISAM;",
      ),
		),
		
		'marcImport' => array(
      'title' => 'Marc Import table',
      'description' => 'Create a table to store information about marc records that are being imported.',
      'dependencies' => array(),
      'sql' => array(
		    'DROP TABLE IF EXISTS marc_import;',
		    "CREATE TABLE IF NOT EXISTS marc_import(" .
			    "`id` VARCHAR(50) COMMENT 'The id of the marc record in the ils', " .
					"`checksum` INT(11) NOT NULL COMMENT 'The timestamp when the reindex started', " .
					"PRIMARY KEY ( `id` )" .
				") ENGINE = MYISAM;",
      ),
		),
		'add_indexes' => array(
			'title' => 'Add indexes',
			'description' => 'Add indexes to tables that were not defined originally',
			'dependencies' => array(),
			'sql' => array(
				'ALTER TABLE `editorial_reviews` ADD INDEX `RecordId` ( `recordId` ) ',
				'ALTER TABLE `list_widget_lists` ADD INDEX `ListWidgetId` ( `listWidgetId` ) ',
				'ALTER TABLE `location` ADD INDEX `ValidHoldPickupBranch` ( `validHoldPickupBranch` ) ',
			),
		),
		
		'add_indexes2' => array(
			'title' => 'Add indexes 2',
			'description' => 'Add additinoal indexes to tables that were not defined originally',
			'dependencies' => array(),
			'sql' => array(
				'ALTER TABLE `user_rating` ADD INDEX `Resourceid` ( `resourceid` ) ',
				'ALTER TABLE `user_rating` ADD INDEX `UserId` ( `userid` ) ',
				'ALTER TABLE `materials_request_status` ADD INDEX ( `isDefault` )',
				'ALTER TABLE `materials_request_status` ADD INDEX ( `isOpen` )',
				'ALTER TABLE `materials_request_status` ADD INDEX ( `isPatronCancel` )',
				'ALTER TABLE `materials_request` ADD INDEX ( `status` )'
			),
		),
		
		'spelling_optimization' => array(
			'title' => 'Spelling Optimization',
			'description' => 'Optimizations to spelling to ensure indexes are used',
			'dependencies' => array(),
			'sql' => array(
				'ALTER TABLE `spelling_words` ADD `soundex` VARCHAR(20) ',
				'ALTER TABLE `spelling_words` ADD INDEX `Soundex` (`soundex`)',
				'UPDATE `spelling_words` SET soundex = SOUNDEX(word) '
			),
		),
		
		
		'remove_old_tables' => array(
			'title' => 'Remove old tables',
			'description' => 'Remove tables that are no longer needed due to usage of memcache',
			'dependencies' => array(),
			'sql' => array(
				//Update resource table indexes
				'DROP TABLE IF EXISTS list_cache',
				'DROP TABLE IF EXISTS list_cache2',
				'DROP TABLE IF EXISTS novelist_cache', 
				'DROP TABLE IF EXISTS reviews_cache',
				'DROP TABLE IF EXISTS sip2_item_cache',
			),
		),
		
		);
	}

	private function checkWhichUpdatesHaveRun($availableUpdates){
		foreach ($availableUpdates as $key=>$update){
			$update['alreadyRun'] = false;
			$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($key) . "'");
			$numRows = mysql_num_rows($result);
			if ($numRows != false){
				$update['alreadyRun'] = true;
			}
			$availableUpdates[$key] = $update;
		}
		return $availableUpdates;
	}

	private function markUpdateAsRun($update_key){
		$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($update_key) . "'");
		if (mysql_num_rows($result) != false){
			//Update the existing value
			mysql_query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = '" . mysql_escape_string($update_key) . "'");
		}else{
			mysql_query("INSERT INTO db_update (update_key) VALUES ('" . mysql_escape_string($update_key) . "')");
		}
	}

	function getAllowableRoles(){
		return array('userAdmin');
	}

	private function createUpdatesTable(){
		//Check to see if the updates table exists
		$result = mysql_query("SHOW TABLES");
		$tableFound = false;
		if ($result){
			while ($row = mysql_fetch_array($result, MYSQL_NUM)){
				if ($row[0] == 'db_update'){
					$tableFound = true;
					break;
				}
			}
		}
		if (!$tableFound){
			//Create the table to mark which updates have been run.
			mysql_query("CREATE TABLE db_update (" .
                    "update_key VARCHAR( 100 ) NOT NULL PRIMARY KEY ," .
                    "date_run TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" .
                    ") ENGINE = InnoDB");
		}
	}

}