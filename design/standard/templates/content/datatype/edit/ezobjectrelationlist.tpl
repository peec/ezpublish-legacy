{* DO NOT EDIT THIS FILE! Use an override template instead. *}
{let class_content=$attribute.class_content
     class_list=fetch( class, list, hash( class_filter, $class_content.class_constraint_list ) )
     can_create=true()
     new_object_initial_node_placement=false()
     browse_object_start_node=false()}

{include uri="design:content/datatype/edit/ezobjectrelationlist_controls.tpl"}

<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
    <th>
    </th>
    <th>
    </th>
    <th>
        Order
    </th>
    <th>
    </th>
</tr>
{section name=Relation loop=$attribute.content.relation_list sequence=array(bglight,bgdark)}
<tr class="{$:sequence}">
    <td width="1" align="right">
        <input type="checkbox" name="{$attribute_base}_selection[{$attribute.id}][]" value="{$:item.contentobject_id}" />
    </td>
    <td width="1">
        {section show=$:item.is_modified|not}
            <input class="button" type="image" name="CustomActionButton[{$attribute.id}_edit_objects_{$:item.contentobject_id}]" value="{'Edit'|i18n('design/standard/content/datatype')}" src={"edit.png"|ezimage} />
        {/section}
    </td>
    <td width="1">
        <input size="2" type="text" name="{$attribute_base}_priority[{$attribute.id}][]" value="{$:item.priority}" />
    </td>
    <td>
        {section show=$:item.is_modified}
            {let object=fetch(content,object,hash(object_id,$:item.contentobject_id,
                                                  object_version,$:item.contentobject_version))
                 version=fetch(content,version,hash(object_id,$:item.contentobject_id,
                                                    version_id,$:item.contentobject_version))}
                <table cellspacing="0" cellpadding="0" border="0">
                {section name=Attribute loop=$:version.contentobject_attributes} 
<tr>
<td>
                    {$:item.contentclass_attribute.name}
</td>
<td>
                    {attribute_edit_gui attribute_base=concat($attribute_base,'_ezorl_edit_object_',$Relation:item.contentobject_id)
                                        html_class='half'
                                        attribute=$:item}
</td>
</tr>
                {/section}
</table>
            {/let}
        {section-else}
            {content_view_gui view=embed content_object=fetch(content,object,hash(object_id,$:item.contentobject_id,
                                                                                  object_version,$:item.contentobject_version))}
        {/section}
    </td>
</tr>
{/section}
</table>

<div class="buttonblock">

    <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_remove_objects]" value="{'Remove objects'|i18n('design/standard/content/datatype')}" />
    <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_edit_objects]" value="{'Open objects for edit'|i18n('design/standard/content/datatype')}" />

    {section show=array( 0, 2 )|contains( $class_content.type )}
        <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_browse_objects]" value="{'Browse for objects'|i18n('design/standard/content/datatype')}" />
        {section show=$browse_object_start_node}
            <input type="hidden" name="{$attribute_base}_browse_for_object_start_node[{$attribute.id}]" value="{$browse_object_start_node|wash}" />
        {/section}
    {/section}

{section show=and( $can_create, array( 0, 1 )|contains( $class_content.type ) )}

    <select class="combobox" name="{$attribute_base}_new_class[{$attribute.id}]">
    {section name=Class loop=$class_list}
        <option value="{$:item.id}">{$:item.name|wash}</option>
    {/section}
    </select>
    {section show=$new_object_initial_node_placement}
        <input type="hidden" name="{$attribute_base}_object_initial_node_placement[{$attribute.id}]" value="{$new_object_initial_node_placement|wash}" />
    {/section}
        <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_new_class]" value="{'Add'|i18n( 'design/standard/content/datatype' )}" />

{/section}

</div>

{/let}
