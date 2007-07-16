<?php
//
// Definition of eZClassFunctionCollection class
//
// Created on: <06-Oct-2002 16:19:31 amos>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.10.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file ezclassfunctioncollection.php
*/

/*!
  \class eZClassFunctionCollection ezclassfunctioncollection.php
  \brief The class eZClassFunctionCollection does

*/

include_once( 'kernel/error/errors.php' );

class eZClassFunctionCollection
{
    /*!
     Constructor
    */
    function eZClassFunctionCollection()
    {
    }

    function fetchClassList( $classFilter, $sortBy )
    {
        $sorts = null;
        if ( $sortBy && 
             is_array( $sortBy ) &&
             count( $sortBy ) == 2 &&
             in_array( $sortBy[0], array( 'id', 'name' ) ) )
        {
            $sorts = array( $sortBy[0] => ( $sortBy[1] )? 'asc': 'desc' );
        }
        $contentClassList = array();
        if ( is_array( $classFilter ) and count( $classFilter ) == 0)
        {
            $classFilter = false;
        }
        if ( !is_array( $classFilter ) or
             count( $classFilter ) > 0 )
        {
            include_once( 'kernel/classes/ezcontentclass.php' );
            $contentClassList = eZContentClass::fetchList( 0, true, false,
                                                            $sorts, null,
                                                            $classFilter );
        }
        if ( $contentClassList === null )
            return array( 'error' => array( 'error_type' => 'kernel',
                                            'error_code' => EZ_ERROR_KERNEL_NOT_FOUND ) );
        return array( 'result' => $contentClassList );
    }

    function fetchLatestClassList( $offset, $limit )
    {
        $contentClassList = array();
        include_once( 'kernel/classes/ezcontentclass.php' );
        $limitData = null;
        if ( $limit )
            $limitData = array( 'offset' => $offset,
                                'length' => $limit );
        $contentClassList = eZContentClass::fetchList( 0, true, false,
                                                        array( 'modified' => 'desc' ), null,
                                                        false, $limitData );
        return array( 'result' => $contentClassList );
    }

    function fetchClassAttributeList( $classID )
    {
        include_once( 'kernel/classes/ezcontentclass.php' );
        $contentClassAttributeList = eZContentClass::fetch( $classID )->fetchAttributes();
        if ( $contentClassAttributeList === null )
            return array( 'error' => array( 'error_type' => 'kernel',
                                            'error_code' => EZ_ERROR_KERNEL_NOT_FOUND ) );
        return array( 'result' => $contentClassAttributeList );
    }

    function fetchOverrideTemplateList( $classID )
    {
        $class = eZContentClass::fetch( $classID );
        $classIdentifier = $class->attribute( 'identifier' );

        $result = array ();

        $ini = eZINI::instance();

        $siteAccessArray = $ini->variable('SiteAccessSettings', 'AvailableSiteAccessList' );

        foreach ( $siteAccessArray as $siteAccess )
        {
            $overrides = eZTemplateDesignResource::overrideArray( $siteAccess );

            foreach( $overrides as $override )
            {
                if ( isset( $override['custom_match'] ) )
                {
                    foreach( $override['custom_match'] as $customMatch )
                    {
                        if( isset( $customMatch['conditions']['class_identifier'] ) &&
                            $customMatch['conditions']['class_identifier'] == $classIdentifier )
                        {
                            $result[] = array( 'siteaccess' => $siteAccess,
                                               'block'      => $customMatch['override_name'],
                                               'source'     => $override['template'],
                                               'target'     => $customMatch['match_file'] );
                        }

                        if( isset( $customMatch['conditions']['class'] ) &&
                            $customMatch['conditions']['class'] == $classID )
                        {

                            $result[] = array( 'siteaccess' => $siteAccess,
                                               'block'      => $customMatch['override_name'],
                                               'source'     => $override['template'],
                                               'target'     => $customMatch['match_file'] );
                        }
                    }
                }
            }

        }

        return array( 'result' => $result );
    }

}

?>
