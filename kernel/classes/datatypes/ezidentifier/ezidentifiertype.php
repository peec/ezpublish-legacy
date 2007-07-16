<?php
//
// Definition of eZIdentifierType class
//
// Created on: <28-Aug-2003 11:43:09 br>
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

/*! \file ezidentifiertype.php
*/

/*!
  \class eZIdentifierType ezidentifiertype.php
  \ingroup eZDatatype
  \brief The class eZIdentifierType does

*/

include_once( "kernel/classes/ezdatatype.php" );
include_once( "lib/ezutils/classes/ezintegervalidator.php" );

define( "EZ_DATATYPESTRING_PRETEXT_FIELD", "data_text1" );
define( "EZ_DATATYPESTRING_PRETEXT_VARIABLE", "_ezidentifier_pretext_value_" );

define( "EZ_DATATYPESTRING_POSTTEXT_FIELD", "data_text2" );
define( "EZ_DATATYPESTRING_POSTTEXT_VARIABLE", "_ezidentifier_posttext_value_" );

define( "EZ_DATATYPESTRING_START_VALUE_FIELD", "data_int1" );
define( "EZ_DATATYPESTRING_START_VALUE_VARIABLE", "_ezidentifier_start_integer_value_" );

define( "EZ_DATATYPESTRING_DIGITS_FIELD", "data_int2" );
define( "EZ_DATATYPESTRING_DIGITS_VARIABLE", "_ezidentifier_digits_integer_value_" );

define( "EZ_DATATYPESTRING_IDENTIFIER_FIELD", "data_int3" );
define( "EZ_DATATYPESTRING_IDENTIFIER_VARIABLE", "_ezidentifier_identifier_value_" );

define( "EZ_DATATYPESTRING_IDENTIFIER", "ezidentifier" );

class eZIdentifierType extends eZDataType
{
    /*!
     Constructor
    */
    function eZIdentifierType()
    {
        $this->eZDataType( EZ_DATATYPESTRING_IDENTIFIER,
                           ezi18n( 'kernel/classes/datatypes', "Identifier", 'Datatype name' ),
                           array( 'serialize_supported' => true,
                                  'object_serialize_map' => array( 'data_text' => 'identifier',
                                                                   'data_int' => 'number' ) ) );
        $this->IntegerValidator = new eZIntegerValidator( 1 );
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
    }

    function fetchObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
    }

    /*!
     Store the content. Since the content has been stored in function fetchObjectAttributeHTTPInput(),
     this function is with empty code.
    */
    function storeObjectAttribute( &$contentObjectattribute )
    {
    }

    /*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->attribute( "data_text" );
        if ( trim( $content ) == '' )
        {
            $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();
            $content = eZIdentifierType::generateIdentifierString( $contentClassAttribute, false );
        }
        return $content;
    }

    function toString( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }


    function fromString( &$contentObjectAttribute, $string )
    {
        if ( $string == '' )
            return true;
        $contentObjectAttribute->setAttribute( 'data_text', $string );
        return true;
    }
    function hasObjectAttributeContent( &$contentObjectAttribute )
    {
        $content = $contentObjectAttribute->attribute( "data_text" );
        return ( trim( $content ) != '' );
    }

    function initializeClassAttribute( &$classAttribute )
    {
        if ( $classAttribute->attribute( EZ_DATATYPESTRING_START_VALUE_FIELD ) == null
          && $classAttribute->attribute( EZ_DATATYPESTRING_DIGITS_FIELD ) == null
          && $classAttribute->attribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD ) == null )
        {
            $classAttribute->setAttribute( EZ_DATATYPESTRING_START_VALUE_FIELD, 1 );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD, 1 );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_DIGITS_FIELD, 1 );
        }
    }

    /*!
      Validates the input and returns true if the input was
      valid for this datatype.
    */
    function validateClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
        $startValueName = $base . EZ_DATATYPESTRING_START_VALUE_VARIABLE . $classAttribute->attribute( "id" );
        $digitsName = $base . EZ_DATATYPESTRING_DIGITS_VARIABLE . $classAttribute->attribute( "id" );

        if ( $http->hasPostVariable( $startValueName ) and
             $http->hasPostVariable( $digitsName ) )
        {
            $startValueValue = str_replace( " ", "", $http->postVariable( $startValueName ) );
            $digitsValue = str_replace( " ", "", $http->postVariable( $digitsName ) );

            $startValueValueState = $this->IntegerValidator->validate( $startValueValue );
            $digitsValueState = $this->IntegerValidator->validate( $digitsValue );

            if ( ( $startValueValueState == EZ_INPUT_VALIDATOR_STATE_ACCEPTED ) and
                 ( $digitsValueState == EZ_INPUT_VALIDATOR_STATE_ACCEPTED ) )
            {
                return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
            }
            return EZ_INPUT_VALIDATOR_STATE_INTERMEDIATE;
        }
        return EZ_INPUT_VALIDATOR_STATE_INVALID;
    }

    /*!
     \reimp
    */
    function fetchClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
        $startValueName = $base . EZ_DATATYPESTRING_START_VALUE_VARIABLE . $classAttribute->attribute( "id" );
        $digitsName = $base . EZ_DATATYPESTRING_DIGITS_VARIABLE . $classAttribute->attribute( "id" );
        $preTextName = $base . EZ_DATATYPESTRING_PRETEXT_VARIABLE . $classAttribute->attribute( "id" );
        $postTextName = $base . EZ_DATATYPESTRING_POSTTEXT_VARIABLE . $classAttribute->attribute( "id" );

        if ( $http->hasPostVariable( $startValueName ) and
             $http->hasPostVariable( $digitsName ) and
             $http->hasPostVariable( $preTextName ) and
             $http->hasPostVariable( $postTextName ) )
        {
            $startValueValue = str_replace( " ", "", $http->postVariable( $startValueName ) );
            $startValueValue = ( int ) $startValueValue;
            if ( $startValueValue < 1 )
            {
                $startValueValue = 1;
            }
            $digitsValue = str_replace( " ", "", $http->postVariable( $digitsName ) );
            $digitsValue = ( int ) $digitsValue;
            if ( $digitsValue < 1 )
            {
                $digitsValue = 1;
            }

            $preTextValue =  $http->postVariable( $preTextName );
            $postTextValue = $http->postVariable( $postTextName );

            $classAttribute->setAttribute( EZ_DATATYPESTRING_DIGITS_FIELD, $digitsValue );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_PRETEXT_FIELD, $preTextValue );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_POSTTEXT_FIELD, $postTextValue );

            $classAttribute->setAttribute( EZ_DATATYPESTRING_START_VALUE_FIELD, $startValueValue );
            $classAttribute->setAttribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD,
                                           $classAttribute->attribute( EZ_DATATYPESTRING_START_VALUE_FIELD ) );

            $originalClassAttribute = eZContentClassAttribute::fetch( $classAttribute->attribute( 'id' ), true, 0 );
            if ( $originalClassAttribute )
            {
                if ( $originalClassAttribute->attribute( EZ_DATATYPESTRING_DIGITS_FIELD ) == $digitsValue
                  && $originalClassAttribute->attribute( EZ_DATATYPESTRING_PRETEXT_FIELD ) == $preTextValue
                  && $originalClassAttribute->attribute( EZ_DATATYPESTRING_POSTTEXT_FIELD ) == $postTextValue
                  && $originalClassAttribute->attribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD ) >= $startValueValue )
                {
                    $classAttribute->setAttribute( EZ_DATATYPESTRING_START_VALUE_FIELD, $originalClassAttribute->attribute( EZ_DATATYPESTRING_START_VALUE_FIELD ) );
                    $classAttribute->setAttribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD, $originalClassAttribute->attribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD ) );
                }
            }
        }
        return true;
    }

    /*!
     Returns the meta data used for storing search indices.
    */
    function metaData( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( "data_text" );
    }

    /*!
     Returns the text.
    */
    function title( $contentObjectAttribute, $name = null )
    {
        return  $contentObjectAttribute->attribute( "data_text" );
    }

    /*!
     \reimp
    */
    function isIndexable()
    {
        return true;
    }


    /*!
     \reimp
    */
    function initializeObjectAttribute( &$contentObjectAttribute, $currentVersion, &$originalContentObjectAttribute )
    {
        $contentObjectAttributeID = $originalContentObjectAttribute->attribute( "id" );
        $version = $contentObjectAttribute->attribute( "version" );
        if ( $currentVersion == false )
        {
            // If this is not a copy we need to see if a unique ID must be
            // assigned. This is handled in assignValue().
            $contentClassAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
            $ret = eZIdentifierType::assignValue( $contentClassAttribute, $contentObjectAttribute );
        }
    }

    /*!
      When published it will check if it needs to aquire a new unique identifier, if so
      it updates all existing versions with this new identifier.
    */
    function onPublish( &$contentObjectAttribute, &$contentObject, &$publishedNodes )
    {
        $contentClassAttribute = $contentObjectAttribute->attribute( 'contentclass_attribute' );
        $ret = eZIdentifierType::assignValue( $contentClassAttribute, $contentObjectAttribute );

        return $ret;
    }

    /*!
      \private
      Assigns the identifiervalue for the first version of the current attribute.
    */
    function assignValue( &$contentClassAttribute, &$contentObjectAttribute )
    {

        $retValue = false;
        $ret = array();
        $version = $contentObjectAttribute->attribute( 'version' );
        $contentClassID = $contentClassAttribute->attribute( 'id' );
        $objectID = (int)$contentObjectAttribute->attribute( 'contentobject_id' );
        $classAttributeID = (int)$contentObjectAttribute->attribute( 'contentclassattribute_id' );

        $db = eZDB::instance();

        $existingIDs = $db->arrayQuery( "SELECT data_int\n" .
                                        "FROM   ezcontentobject_attribute\n" .
                                        "WHERE  contentobject_id = $objectID AND\n" .
                                        "       contentclassattribute_id = $classAttributeID AND\n" .
                                        "       data_type_string = 'ezidentifier' AND\n" .
                                        "       data_int != 0" );
        if ( count( $existingIDs ) > 0 )
        {
            $identifierValue = $existingIDs[0]['data_int'];
            $ret[] = eZIdentifierType::storeIdentifierValue( $contentClassAttribute, $contentObjectAttribute, $identifierValue );
        }
        else
        {
            $db->begin();

            // Ensure that we don't get another identifier with the same id.
            $db->lock( array( array( "table" => "ezcontentobject_attribute" ),
                              array( "table" => "ezcontentclass_attribute" ) ) );

            $selectQuery = "SELECT data_int3 FROM ezcontentclass_attribute WHERE " .
                 "id='$contentClassID' AND version='0'";
            $result = $db->arrayQuery( $selectQuery );
            $identifierValue = $result[0]['data_int3'];

            // should only increment when we don't have the first version
            $updateQuery = "UPDATE ezcontentclass_attribute SET data_int3=data_int3 + 1 WHERE " .
                  "id='$contentClassID' AND version='0'";

            $ret[] = $db->query( $updateQuery );
            $ret[] = eZIdentifierType::storeIdentifierValue( $contentClassAttribute, $contentObjectAttribute, $identifierValue );

            if ( !in_array( false, $ret ) )
            {
                // Now make sure all existing versions (if any) gets the same identifier
                $dataText = $db->escapeString( $contentObjectAttribute->attribute( 'data_text' ) );
                $dataInt = (int)$contentObjectAttribute->attribute( 'data_int' );

                include_once( 'lib/ezi18n/classes/ezchartransform.php' );
                $trans = eZCharTransform::instance();
                $sortText = $db->escapeString( $trans->transformByGroup( $contentObjectAttribute->attribute( 'data_text' ),
                                                                         'lowercase' ) );

                $db->query( "UPDATE ezcontentobject_attribute\n" .
                            "SET    data_text = '$dataText', data_int = $dataInt, sort_key_string = '$sortText'\n" .
                            "WHERE  contentobject_id = $objectID AND\n" .
                            "       contentclassattribute_id = $classAttributeID AND\n" .
                            "       data_type_string = 'ezidentifier'" );
            }

            if ( !in_array( false, $ret ) )
                $db->commit();
            else
                $db->rollback();

            $db->unlock();
        }

        if ( !in_array( false, $ret ) )
            $retValue = true;

        return $retValue;
    }

    /*!
     \reimp
    */
    function sortKey( &$contentObjectAttribute )
    {
        include_once( 'lib/ezi18n/classes/ezchartransform.php' );
        $trans = eZCharTransform::instance();
        return $trans->transformByGroup( $contentObjectAttribute->attribute( 'data_text' ), 'lowercase' );
    }

    /*!
    \reimp
    */
    function sortKeyType()
    {
        return 'string';
    }

    /*!
      \private
      Store the new value to the attribute.
    */
    function storeIdentifierValue( &$contentClassAttribute, &$contentObjectAttribute, $identifierValue )
    {
        $value = eZIdentifierType::generateIdentifierString( $contentClassAttribute, $identifierValue );
        $contentObjectAttribute->setAttribute( 'data_text', $value );
        $contentObjectAttribute->setAttribute( 'data_int', $identifierValue );
        return true;
    }

    function generateIdentifierString( &$contentClassAttribute, $identifierValue = false )
    {
        $preText = $contentClassAttribute->attribute( EZ_DATATYPESTRING_PRETEXT_FIELD );
        $postText = $contentClassAttribute->attribute( EZ_DATATYPESTRING_POSTTEXT_FIELD );
        $digits = $contentClassAttribute->attribute( EZ_DATATYPESTRING_DIGITS_FIELD );

        if ( $identifierValue !== false )
            $midText = str_pad( $identifierValue, $digits, '0', STR_PAD_LEFT );
        else
            $midText = str_repeat( 'x', $digits );

        $value = $preText . $midText . $postText;
        return $value;
    }

    function customClassAttributeHTTPAction( $http, $action, $contentClassAttribute )
    {
    }

    function preStoreClassAttribute( &$classAttribute, $version )
    {
    }

    function preStoreDefinedClassAttribute( &$classAttribute )
    {
    }

    /*!
     \reimp
    */
    function serializeContentClassAttribute( &$classAttribute, &$attributeNode, &$attributeParametersNode )
    {
        $digits     = $classAttribute->attribute( EZ_DATATYPESTRING_DIGITS_FIELD );
        $preText    = $classAttribute->attribute( EZ_DATATYPESTRING_PRETEXT_FIELD );
        $postText   = $classAttribute->attribute( EZ_DATATYPESTRING_POSTTEXT_FIELD );
        $startValue = $classAttribute->attribute( EZ_DATATYPESTRING_START_VALUE_FIELD );
        $identifier = $classAttribute->attribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD );

        $dom = $attributeParametersNode->ownerDocument;

        $digitsNode = $dom->createElement( 'digits', $digits );
        $attributeParametersNode->appendChild( $digitsNode );
        $preTextNode = $dom->createElement( 'pre-text', $preText );
        $attributeParametersNode->appendChild( $preTextNode );
        $postTextNode = $dom->createElement( 'post-text', $postText );
        $attributeParametersNode->appendChild( $postTextNode );
        $startValueNode = $dom->createElement( 'start-value', $startValue );
        $attributeParametersNode->appendChild( $startValueNode );
        $identifierNode = $dom->createElement( 'identifier', $identifier );
        $attributeParametersNode->appendChild( $identifierNode );
    }

    /*!
     \reimp
    */
    function unserializeContentClassAttribute( &$classAttribute, &$attributeNode, &$attributeParametersNode )
    {
        $digits     = $attributeParametersNode->getElementsByTagName( 'digits' )->item( 0 )->textContent;
        $preText    = $attributeParametersNode->getElementsByTagName( 'pre-text' )->item( 0 )->textContent;
        $postText   = $attributeParametersNode->getElementsByTagName( 'post-text' )->item( 0 )->textContent;
        $startValue = $attributeParametersNode->getElementsByTagName( 'start-value' )->item( 0 )->textContent;
        $identifier = $attributeParametersNode->getElementsByTagName( 'identifier' )->item( 0 )->textContent;

        if ( $digits !== false )
            $classAttribute->setAttribute( EZ_DATATYPESTRING_DIGITS_FIELD,      $digits );

        if ( $preText !== false )
            $classAttribute->setAttribute( EZ_DATATYPESTRING_PRETEXT_FIELD,     $preText );

        if ( $postText !== false )
            $classAttribute->setAttribute( EZ_DATATYPESTRING_POSTTEXT_FIELD,    $postText );

        if ( $startValue !== false )
            $classAttribute->setAttribute( EZ_DATATYPESTRING_START_VALUE_FIELD, $startValue );

        if ( $identifier !== false )
            $classAttribute->setAttribute( EZ_DATATYPESTRING_IDENTIFIER_FIELD,  $identifier );
    }

    public $IntegerValidator;
}

eZDataType::register( EZ_DATATYPESTRING_IDENTIFIER, "ezidentifiertype" );

?>
