<?php
//
// eZSetup
//
// Created on: <08-Nov-2002 11:00:54 kd>
//
// Copyright (C) 1999-2002 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/home/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

// All test functions should be defined in ezsetuptests
include( "kernel/setup/ezsetuptests.php" );

define( 'EZ_SETUP_DB_ERROR_EMPTY_PASSWORD', 1 );
define( 'EZ_SETUP_DB_ERROR_NONMATCH_PASSWORD', 2 );

/*!
	Prepare the sql file so we can create the database.
*/
function prepareSqlQuery( $path, $type, $file )
{
    include_once( 'lib/ezutils/classes/ezdir.php' );
    $sqlFile = eZDir::path( array( $path, $type, $file ) );
    eZDebug::writeDebug( "reading sql from file $sqlFile" );
    $sqlQuery = fread( fopen( $sqlFile, 'r' ), filesize( $sqlFile ));
    if ( $sqlQuery )
    {
	    // Fix SQL file by deleting all comments and newlines
	    $sqlQuery = preg_replace( array( "/^#.*" . "/m", "/^--.*" . "/m" ), array( " ", " " ), $sqlQuery );

	    // Split the query into an array
	    $sqlQueryArray = preg_split( '/;$/m', $sqlQuery );

		return $sqlQueryArray;
	}
	else
	{
		return false;
	}
}

function doQuery( &$dbObject, $path, $sqlFile )
{
    $type = $dbObject->databaseName();
    set_time_limit(0);

	$sqlArray = prepareSqlQuery( $path, $type, $sqlFile );

	// Turn unneccessary SQL debug output off
	$dbObject->OutputSQL = false;
	if ( $sqlArray && is_array( $sqlArray ) )
	{
		foreach( $sqlArray as $singleQuery )
		{
            $singleQuery = preg_replace( "/\n|\r\n|\r/", " ", $singleQuery );
			if ( trim( $singleQuery ) != "" )
			{
//                eZDebug::writeDebug( $singleQuery );
				$dbObject->query( $singleQuery );
				if ( $dbObject->errorNumber() != 0 )
					return false;
			}
		}
		return true;
	}
    return false;
}

/*!
    Step 1: General tests and information for the databases
*/
function eZSetupStep_database_init( &$tpl, &$http, &$ini, &$persistenceList )
{
    $databaseMap = eZSetupDatabaseMap();
    $template = "design:setup/init/database_init.tpl";

    $config =& eZINI::instance( 'setup.ini' );
    if ( !$persistenceList['database_info']['server'] )
        $persistenceList['database_info']['server'] = $config->variable( 'DatabaseSettings', 'DefaultServer' );
    if ( !$persistenceList['database_info']['name'] )
        $persistenceList['database_info']['name'] = $config->variable( 'DatabaseSettings', 'DefaultName' );
    if ( !$persistenceList['database_info']['user'] )
        $persistenceList['database_info']['user'] = $config->variable( 'DatabaseSettings', 'DefaultUser' );
    if ( !$persistenceList['database_info']['user'] )
        $persistenceList['database_info']['user'] = $config->variable( 'DatabaseSettings', 'DefaultUser' );

    include_once( 'lib/ezutils/classes/ezhttptool.php' );
    $http =& eZHTTPTool::instance();
    if ( $http->hasPostVariable( 'eZSetupDatabaseServer' ) )
        $persistenceList['database_info']['server'] = $http->postVariable( 'eZSetupDatabaseServer' );
    if ( $http->hasPostVariable( 'eZSetupDatabaseName' ) )
        $persistenceList['database_info']['name'] = $http->postVariable( 'eZSetupDatabaseName' );
    if ( $http->hasPostVariable( 'eZSetupDatabaseUser' ) )
        $persistenceList['database_info']['user'] = $http->postVariable( 'eZSetupDatabaseUser' );

    $error = 0;

    $dbStatus = false;
    $databaseReady = false;
    if ( $http->hasPostVariable( 'eZSetupDatabasePassword' ) )
    {
        $password = $http->postVariable( 'eZSetupDatabasePassword' );
        $passwordConfirm = $http->postVariable( 'eZSetupDatabasePasswordConfirm' );
        if ( !$password )
        {
            $error = EZ_SETUP_DB_ERROR_EMPTY_PASSWORD;
        }
        else if ( $password != $passwordConfirm )
        {
            $error = EZ_SETUP_DB_ERROR_NONMATCH_PASSWORD;
        }
        else
        {
            $persistenceList['database_info']['password'] = $password;
            $databaseReady = true;
        }
    }
    if ( $http->hasPostVariable( 'eZSetupDatabaseReady' ) )
        $databaseReady = true;

    $databaseChoice = false;
    if ( $http->hasPostVariable( 'eZSetupDatabaseDataChoice' ) )
    {
        $databaseChoice = $http->postVariable( 'eZSetupDatabaseDataChoice' );
        if ( $databaseChoice == 3 )
            $databaseReady = false;
        $password = $persistenceList['database_info']['password'];
    }

    $databaseInfo = $persistenceList['database_info'];
    $databaseInfo['info'] = $databaseMap[$databaseInfo['type']];
    $regionalInfo = $persistenceList['regional_info'];

    if ( $databaseReady )
    {
        $dbStatus = array();
        $dbDriver = $databaseInfo['info']['driver'];
        $dbServer = $databaseInfo['server'];
        $dbName = $databaseInfo['name'];
        $dbUser = $databaseInfo['user'];
        $dbPwd = $password;
        $dbCharset = 'iso-8859-1';
        $dbParameters = array( 'server' => $dbServer,
                               'user' => $dbUser,
                               'password' => $dbPwd,
                               'database' => $dbName,
                               'charset' => $dbCharset );
        $db =& eZDB::instance( $dbDriver, $dbParameters );
        $dbStatus['connected'] = $db->isConnected();

        $dbError = false;
        if ( $dbStatus['connected'] )
        {
            $template = "design:setup/init/database_check.tpl";
            if ( !isset( $persistenceList['demo_data']['use'] ) )
                $persistenceList['demo_data']['use'] = false;
            if ( $http->hasPostVariable( 'eZSetupDemoData' ) )
                $persistenceList['demo_data']['use'] = true;
            $demoData = $persistenceList['demo_data'];
            $tpl->setVariable( 'demo_data', $demoData );

            $tableCount = $db->tableCount();
            $databaseInfo['table']['count'] = $tableCount;

            if ( $databaseChoice == 2 )
            {
                $tableList = $db->tableList();
                $dbObject->OutputSQL = false;
                foreach ( $tableList as $tableName )
                {
                    $sql = "DROP TABLE $tableName";
                    $result = $db->query( $sql );
                    if ( !$result )
                    {
                        $dbError = true;
                        break;
                    }
                }
            }
            if ( !$dbError and
                 ( $databaseChoice == 1 or
                   $databaseChoice == 2 ) )
            {
                $setupINI =& eZINI::instance( 'setup.ini' );
                $sqlFile = $setupINI->variable( 'DatabaseSettings', 'CleanSQL' );
                if ( $demoData['use'] )
                    $sqlFile = $setupINI->variable( 'DatabaseSettings', 'DemoSQL' );
                $result = doQuery( $db, 'kernel/sql/', $sqlFile );
                if ( !$result )
                {
                    $dbError = true;
                }
                else
                {
                    $template = "design:setup/init/database_done.tpl";
                    $persistenceList['database_info']['initialized'] = true;
                }
            }
        }
        else
            $dbError = true;
        $dbStatus['error'] = false;
        if ( $dbError )
        {
            $dbStatus['error'] = array( 'text' => $db->errorMessage(),
                                        'number' => $db->errorNumber() );
        }
    }
    $tpl->setVariable( 'database_info', $databaseInfo );
    $tpl->setVariable( 'regional_info', $regionalInfo );
    $tpl->setVariable( 'database_status', $dbStatus );

    $tpl->setVariable( 'error', $error );

    $result = array();
    // Display template
    $result['content'] = $tpl->fetch( $template );
    $result['path'] = array( array( 'text' => 'Database initalization',
                                    'url' => false ) );
    return $result;
}


?>
