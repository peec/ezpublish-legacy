<?php
//
// Definition of eZTemplateSwitchFunction class
//
// Created on: <06-Mar-2002 08:07:54 amos>
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

/*!
  \class eZTemplateSwitchFunction eztemplateswitchfunction.php
  \ingroup eZTemplateFunctions
  \brief Handles conditional output in templates using function "switch"

  This allows for writing switch/case sentences (similar to if/else if/else)
  which you normally find in programming languages. With this you can display
  text depending on a certain template variable.

\code
// Example template code
{* Matches $a against $b or $c *}
{switch match=$a}
{case match=$b}
Matched $b
{/case}
{case match=$c}
Matched $c
{/case}
{/switch}

\endcode

*/

class eZTemplateSwitchFunction
{
    /*!
     Initializes the function with the name $name, default is "switch".
    */
    function eZTemplateSwitchFunction( $name = "switch" )
    {
        $this->Name = $name;
    }

    /*!
     Returns an array of the function names, required for eZTemplate::registerFunctions.
    */
    function &functionList()
    {
        return array( $this->Name );
    }

    /*!
     Returns the attribute list which is case.
    */
    function attributeList()
    {
        return array( "case" => true );
    }

    /*!
     Processes the function with all it's children.
    */
    function process( &$tpl, &$textElements, $functionName, $functionChildren, $functionParameters, $functionPlacement, $rootNamespace, $currentNamespace )
    {
//         $text = "";
        $children = $functionChildren;
        $params = $functionParameters;
        $name = "";
        if ( isset( $params["name"] ) )
            $name = $tpl->elementValue( $params["name"], $rootNamespace, $currentNamespace, $functionPlacement );
        if ( $currentNamespace != "" )
        {
            if ( $name != "" )
                $name = "$currentNamespace:$name";
            else
                $name = $currentNamespace;
        }
        if ( isset( $params["match"] ) )
            $match = $tpl->elementValue( $params["match"], $rootNamespace, $currentNamespace, $functionPlacement );
        else
        {
            $tpl->missingParameter( $this->Name, "match" );
            return false;
        }

        $items = array();
        $in_items = array();
        $def = null;
        $case = null;
        reset( $children );
        while ( ( $child_key = key( $children ) ) !== null )
        {
            $child =& $children[$child_key];
            $childType = $child[0];
            if ( $childType == EZ_TEMPLATE_NODE_FUNCTION )
            {
                switch ( $child[2] )
                {
                    case "case":
                    {
                        $child_params = $child[3];
                        if ( isset( $child_params["match"] ) )
                        {
                            $child_match = $child_params["match"];
                            $child_match = $tpl->elementValue( $child_match, $rootNamespace, $currentNamespace, $functionPlacement );
                            if ( !isset( $items[$child_match] ) )
                            {
                                $items[$child_match] =& $child;
                                if ( is_null( $case ) and
                                     $match == $child_match )
                                {
                                    $case =& $child;
                                }
                            }
                            else
                                $tpl->warning( $this->Name, "Match value $child_match already set, skipping" );
//                             }
//                             else
//                                 $tpl->warning( $this->Name, "Match value $child_match for case is not set" );
                        }
                        else if ( isset( $child_params["in"] ) )
                        {
                            $key_name = null;
                            if ( isset( $child_params["key"] ) )
                            {
                                $child_key = $child_params["key"];
                                $key_name = $tpl->elementValue( $child_key, $rootNamespace, $currentNamespace, $functionPlacement );
                            }
                            $child_in = $child_params["in"];
                            $child_in = $tpl->elementValue( $child_in, $rootNamespace, $currentNamespace, $functionPlacement );
                            if ( !is_array( $child_in ) )
                                break;
                            if ( is_null( $case ) )
                            {
                                if ( is_null( $key_name ) and
                                     in_array( $match, $child_in ) )
                                {
                                    $case =& $child;
                                }
                                else
                                {
                                    reset( $child_in );
                                    while( ( $ckey = key( $child_in ) ) !== null )
                                    {
//                                         if ( $child_in[$ckey][$key_name] == $match )
                                        if ( !is_array( $key_name ) )
                                            $key_name_array = array( $key_name );
                                        else
                                            $key_name_array = $key_name;
                                        $child_value = $tpl->variableAttribute( $child_in[$ckey], $key_name );
                                        if ( $child_value == $match )
                                        {
                                            $case =& $child;
                                            break;
                                        }
                                        next( $child_in );
                                    }
                                }
                            }
//                             }
//                             else
//                                 $tpl->warning( $this->Name, "In value $child_in for case is not set" );
                        }
                        else
                        {
                            $def =& $child;
                        }
                    } break;
                    default:
                    {
                        $tpl->warning( $this->Name, "Only case functions are allowed as children, found \""
                                       . $child[2] . "\"" );
                    } break;
                }
            }
            else if ( $childType == EZ_TEMPLATE_NODE_TEXT )
            {
                // Ignore text.
            }
            else
            {
                $tpl->warning( $this->Name, "Only functions are allowed as children, found \""
                               . $childType . "\"" );
            }
            next( $children );
        }
        if ( is_null( $case ) )
            $case =& $def;

        if ( $case !== null )
        {
            $tpl->setVariable( "match", $match, $name );
            $case_children =& $case[1];
            if ( $case_children )
            {
                reset( $case_children );
                while ( ( $key = key( $case_children ) ) !== null )
                {
                    $case_child =& $case_children[$key];
                    $tpl->processNode( $case_child, $textElements, $rootNamespace, $name );
                    next( $case_children );
                }
            }
        }
        else
            $tpl->warning( $this->Name, "No case match and no default case" );
        return;
    }

    /*!
     Returns true.
    */
    function hasChildren()
    {
        return true;
    }

    /// The name of the switch function
    var $Name;
}

?>
