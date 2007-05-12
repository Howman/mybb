<?php
/**
 * MyBB 1.2
 * Copyright � 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/license.php
 *
 * $Id$
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->attachment_types, "index.php?".SID."&amp;module=config/attachment_types");


if($mybb->input['action'] == "add")
{
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['mimetype']))
		{
			$errors[] = $lang->error_missing_mime_type;
		}

		if(!trim($mybb->input['extension']))
		{
			$errors[] = $lang->error_missing_extension;
		}

		if(!$errors)
		{
			if($mybb->input['mimetype'] == "images/attachtypes/")
			{
				$mybb->input['mimetype'] = '';
			}

			$new_type = array(
				"mimetype" => $db->escape_string($mybb->input['mimetype']),
				"extension" => $db->escape_string($mybb->input['extension']),
				"maxsize" => intval($mybb->input['maxsize']),
				"icon" => $db->escape_string($mybb->input['icon'])
			);

			$db->insert_query("attachtypes", $new_type);

			$cache->update_attachtypes();

			flash_message($lang->attachment_type_created, 'success');
			admin_redirect("index.php?".SID."&module=config/attachment_types");
		}
	}

	
	$page->add_breadcrumb_item($lang->add_attachment_type);
	$page->output_header($lang->attachment_types." - ".$lang->add_attachment_type);
	
	$sub_tabs['add_attachment_type'] = array(
		'title' => $lang->add_attachment_type,
		'description' => $lang->add_attachment_type_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'add_attachment_type');

	$form = new Form("index.php?".SID."&amp;module=config/attachment_types&amp;action=add", "post", "add");
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['maxsize'] = '1024';
		$mybb->input['icon'] = "images/attachtypes/";
	}
	
	$form_container = new FormContainer($lang->add_new_attachment_type);
	$form_container->output_row($lang->file_extension." <em>*</em>", $lang->file_extension_desc, $form->generate_text_box('extension', $mybb->input['extension'], array('id' => 'extension')), 'extension');
	$form_container->output_row($lang->mime_type." <em>*</em>", $lang->mime_type_desc, $form->generate_text_box('mimetype', $mybb->input['mimetype'], array('id' => 'mimetype')), 'mimetype');
	$form_container->output_row($lang->maximum_file_size, $lang->maximum_file_size_desc, $form->generate_text_box('maxsize', $mybb->input['maxsize'], array('id' => 'maxsize')), 'maxsize');
	$form_container->output_row($lang->attachment_icon, $lang->attachment_icon_desc, $form->generate_text_box('icon', $mybb->input['icon'], array('id' => 'icon')), 'icon');

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_attachment_type);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit")
{
	$query = $db->simple_select("attachtypes", "*", "atid='".intval($mybb->input['atid'])."'");
	$attachment_type = $db->fetch_array($query);

	if(!$attachment_type['atid'])
	{
		flash_message($lang->error_invalid_attachment_type, 'error');
		admin_redirect("index.php?".SID."&module=config/attachment_types");
	}
		
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['mimetype']))
		{
			$errors[] = $lang->error_missing_mime_type;
		}

		if(!trim($mybb->input['extension']))
		{
			$errors[] = $lang->error_missing_extension;
		}

		if(!$errors)
		{
			if($mybb->input['mimetype'] == "images/attachtypes/")
			{
				$mybb->input['mimetype'] = '';
			}

			$updated_type = array(
				"mimetype" => $db->escape_string($mybb->input['mimetype']),
				"extension" => $db->escape_string($mybb->input['extension']),
				"maxsize" => intval($mybb->input['maxsize']),
				"icon" => $db->escape_string($mybb->input['icon'])
			);

			$db->update_query("attachtypes", $updated_type, "atid='{$attachment_type['atid']}'");

			$cache->update_attachtypes();

			flash_message($lang->success_attachment_type_updated, 'success');
			admin_redirect("index.php?".SID."&module=config/attachment_types");
		}
	}
	
	$page->add_breadcrumb_item($lang->edit_attachment_type);
	$page->output_header($lang->attachment_types." - ".$lang->edit_attachment_type);
	
	$sub_tabs['edit_attachment_type'] = array(
		'title' => $lang->edit_attachment_type,
		'description' => $lang->edit_attachment_type_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'edit_attachment_type');

	$form = new Form("index.php?".SID."&amp;module=config/attachment_types&amp;action=edit&amp;atid={$attachment_type['atid']}", "post", "add");

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input = $attachment_type;
	}
	
	$form_container = new FormContainer($lang->edit_attachment_type);
	$form_container->output_row($lang->file_extension." <em>*</em>", $lang->file_extension_desc, $form->generate_text_box('extension', $mybb->input['extension'], array('id' => 'extension')), 'extension');
	$form_container->output_row($lang->mime_type." <em>*</em>", $lang->mime_type_desc, $form->generate_text_box('mimetype', $mybb->input['mimetype'], array('id' => 'mimetype')), 'mimetype');
	$form_container->output_row($lang->maximum_file_size, $lang->maximum_file_size_desc, $form->generate_text_box('maxsize', $mybb->input['maxsize'], array('id' => 'maxsize')), 'maxsize');
	$form_container->output_row($lang->attachment_icon, $lang->attachment_icon_desc, $form->generate_text_box('icon', $mybb->input['icon'], array('id' => 'icon')), 'icon');

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_attachment_type);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete")
{
	if($mybb->input['no']) 
	{ 
		admin_redirect("index.php?".SID."&module=config/attachment_types"); 
	}
	
	$query = $db->simple_select("attachtypes", "atid", "atid='".intval($mybb->input['atid'])."'");
	$atid = $db->fetch_field($query, "atid");

	if(!$atid)
	{
		flash_message($lang->error_invalid_attachment_type, 'error');
		admin_redirect("index.php?".SID."&module=config/attachment_types");
	}
	
	if($mybb->request_method == "post")
	{
		$db->delete_query("attachtypes", "atid='{$atid}'");

		$cache->update_attachtypes();

		flash_message($lang->success_attachment_type_deleted, 'success');
		admin_redirect("index.php?".SID."&module=config/attachment_types");
	}
	else
	{
		$page->output_confirm_action("index.php?".SID."&amp;module=config/attachment_types&amp;action=delete&amp;atid={$mybb->input['atid']}", $lang->confirm_attachment_type_deletion); 
	}
}

if(!$mybb->input['action'])
{
	$page->output_header("Attachment Types");

	$sub_tabs['attachment_types'] = array(
		'title' => $lang->attachment_types,
		'link' => "index.php?".SID."&amp;module=config/attachment_types",
		'description' => $lang->attachment_types_desc
	);
	$sub_tabs['add_attachment_type'] = array(
		'title' => $lang->add_new_attachment_type,
		'link' => "index.php?".SID."&amp;module=config/attachment_types&amp;action=add",
	);

	$page->output_nav_tabs($sub_tabs, 'attachment_types');
	
	$table = new Table;
	$table->construct_header($lang->extension, array("colspan" => 2));
	$table->construct_header($lang->mime_type);
	$table->construct_header($lang->maximum_size, array("class" => "align_center"));
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2));
	
	$query = $db->simple_select("attachtypes", "*", "", array('order_by' => 'extension'));
	while($attachment_type = $db->fetch_array($query))
	{
		if(!$attachment_type['icon'] || $attachment_type['icon'] == "images/attachtypes/")
		{
			$attachment_type['icon'] = "&nbsp;";
		}
		else
		{
			$attachment_type['icon'] = "<img src=\"../{$attachment_type['icon']}\" alt=\"\" />";
		}
		$table->construct_cell($attachment_type['icon'], array("width" => 1));
		$table->construct_cell("<strong>.{$attachment_type['extension']}</strong>");
		$table->construct_cell($attachment_type['mimetype']);
		$table->construct_cell(get_friendly_size($attachment_type['maxsize']), array("class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?".SID."&amp;module=config/attachment_types&amp;action=edit&amp;atid={$attachment_type['atid']}\">{$lang->edit}</a>", array("class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?".SID."&amp;module=config/attachment_types&amp;action=delete&amp;atid={$attachment_type['atid']}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_attachment_type_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
		$table->construct_row();
	}
	
	if(count($table->rows) == 0)
	{
		$table->construct_cell($lang->no_attachment_types, array('colspan' => 6));
		$table->construct_row();
	}
	
	$table->output($lang->attachment_types);
	
	$page->output_footer();
}
?>
