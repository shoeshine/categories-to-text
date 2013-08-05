<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Categories to Text
 *
 * Saves a channel entry's selected category names into a text field
 * @category  Extension
 * @author    John Langer (jlanger@sonic.net) 
 * @copyright Copyright (c) 2013
 * @version 0.9.6
 */

class Categories_to_text_ext {

	var $name           = 'Categories to Text';
	var $version        = '0.9.6';
	var $description    = 'Concatenate a channel entry\'s category names into a text field';
	var $settings_exist = 'y';
	var $docs_url       = '';

	var $settings       = array();

	
	/**
	 * Constructor
	 *
	 * @param  mixed  $settings array or empty string if none exists.
	 */
	function __construct($settings='')
	{
		$this->EE =& get_instance();

		$this->settings = $settings;
	}

	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @return  void
	 */
	function activate_extension()
	{
		$this->settings = array();
		$this->settings['channel'] = array();

		$data = array(
			'class'    => __CLASS__,
			'method'   => 'cat_to_text',
			'hook'     => 'entry_submission_end',
			'settings' => serialize($this->settings),
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		);

		$this->EE->db->insert('extensions', $data);

	}


	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return  mixed  void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
								'extensions',
								array('version' => $this->version)
		); 
	}


	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return  void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}


	/*
	 *
	 *
	 *
	 */
	function cat_to_text($entry_id, $meta, $data)
	{
		$catPostT  = $this->EE->db->dbprefix . 'category_posts';
		$catT      = $this->EE->db->dbprefix . 'categories';
		$chDataT   = $this->EE->db->dbprefix . 'channel_data';
		$catFDataT = $this->EE->db->dbprefix . 'category_field_data';

		// get channel_id for this entry_id
		$results = $this->EE->db->query('SELECT channel_id FROM '.$chDataT.' WHERE entry_id='.$entry_id);
		if ($results->num_rows() > 0)
		{
			$channel_id = $results->row('channel_id');
		} else {
			$channel_id = '';
		}

		// based on that channel_id, pull preferences from $settings['channel'][$channel_id]
		if (array_key_exists ($channel_id, $this->settings['channel']))
		{
			$prefs = $this->settings['channel'][$channel_id];

			// for each category_group_id to text_field_id pair
			foreach ($prefs as $index=>$pair)
			{
				// find all categories from that category_group_id for this entry_id
				$category_group_id = $pair['category_group_id'];
				$text_field_id     = $pair['text_field_id'];
				$category_field_id = $pair['category_field_id'];
				if ($category_group_id != '')
				{
					$results = $this->EE->db->query('SELECT c.cat_id, cat_name FROM '.$catPostT.' cp JOIN '.$catT.' c ON cp.cat_id=c.cat_id WHERE cp.entry_id='.$entry_id.' && c.group_id='.$category_group_id);
					$text_str = '';
					if ($results->num_rows() > 0)
					{
						foreach ($results->result_array() as $row)
						{
							// concatenate together with commas between
							$text_str.= $row['cat_name'].', ';

							// find all synonmys
							$cat_id   = $row['cat_id'];
							if ($category_field_id != '' && $category_field_id != NULL)
							{
								$results = $this->EE->db->query('SELECT field_id_'.$category_field_id.' FROM '.$catFDataT.' WHERE cat_id='.$cat_id);
								if ($results->num_rows() > 0)
								{
									$text_to_add = $results->row('field_id_'.$category_field_id);
									if ($text_to_add != '')
									{
										// add to string
										$text_str.= $text_to_add.', ';
									}
								}
							}				
						}
						// remove trailing space and comma
						$text_str = substr($text_str, 0, -2);
					}

					// put string in text_field_id field
					if ($text_field_id != '' && $text_field_id != NULL)
					{
						$data = array('field_id_'.$text_field_id => $text_str);
						$sql = $this->EE->db->update_string($chDataT, $data, "entry_id='".$entry_id."'");
						$this->EE->db->query($sql);
					}
				}
			}
		}
	}


	/*
	 * to initialize existing channel entries
	 * only difference from cat_to_text right now is
	 * $settings (passed as a parameter) instead of $this->settings
	 */
	function cat_to_text_init ($entry_id, $settings)
	{
		$catPostT  = $this->EE->db->dbprefix . 'category_posts';
		$catT      = $this->EE->db->dbprefix . 'categories';
		$chDataT   = $this->EE->db->dbprefix . 'channel_data';
		$catFDataT = $this->EE->db->dbprefix . 'category_field_data';

		// get channel_id for this entry_id
		$results = $this->EE->db->query('SELECT channel_id FROM '.$chDataT.' WHERE entry_id='.$entry_id);
		if ($results->num_rows() > 0)
		{
			$channel_id = $results->row('channel_id');
		} else {
			$channel_id = '';
		}

		// based on that channel_id, pull preferences from $channel_settings[$channel_id]
		if (array_key_exists ($channel_id, $settings['channel']))
		{
			$prefs = $settings['channel'][$channel_id];

			// for each category_group_id to text_field_id pair
			foreach ($prefs as $index=>$pair)
			{
				// find all categories from that category_group_id for this entry_id
				$category_group_id = $pair['category_group_id'];
				$text_field_id     = $pair['text_field_id'];
				$category_field_id = $pair['category_field_id'];
				if ($category_group_id != '')
				{
					$results = $this->EE->db->query('SELECT c.cat_id, cat_name FROM '.$catPostT.' cp JOIN '.$catT.' c ON cp.cat_id=c.cat_id WHERE cp.entry_id='.$entry_id.' && c.group_id='.$category_group_id);
					$text_str = '';
					if ($results->num_rows() > 0)
					{
						foreach ($results->result_array() as $row)
						{
							// concatenate together with commas between
							$text_str.= $row['cat_name'].', ';

							// find all synonmys
							$cat_id   = $row['cat_id'];
							if ($category_field_id != '' && $category_field_id != NULL)
							{
								$results = $this->EE->db->query('SELECT field_id_'.$category_field_id.' FROM '.$catFDataT.' WHERE cat_id='.$cat_id);
								if ($results->num_rows() > 0)
								{
									$text_to_add = $results->row('field_id_'.$category_field_id);
									if ($text_to_add != '')
									{
										// add to string
										$text_str.= $text_to_add.', ';
									}
								}
							}
						}
						// remove trailing space and comma
						$text_str = substr($text_str, 0, -2);
					}

					// put string in text_field_id field
					if ($text_field_id != '' && $text_field_id != NULL)
					{
						$data = array('field_id_'.$text_field_id => $text_str);
						$sql = $this->EE->db->update_string($chDataT, $data, "entry_id='".$entry_id."'");
						$this->EE->db->query($sql);
					}
				}
			}
		}
	}


	/**
	 * Settings Form
	 *
	 * @param   Array      Settings
	 * @return  void
	 */
	function settings_form($current)
	{
		$this->EE->load->helper('form');
		$this->EE->load->library('table');
//		$this->EE->cp->load_package_js('cat_to_txt');

		$query = get_instance()->db->select('settings')
			                       ->where('class', __CLASS__)
			                       ->get('extensions');
		$settings = unserialize($query->row('settings'));

		// build array of possible category groups and category fields for each channel
		$cT  = $this->EE->db->dbprefix . "channels";
		$cgT = $this->EE->db->dbprefix . "category_groups";
		$channel_cat_list = array();
		$channels = $this->EE->db->query('SELECT channel_id, channel_title, cat_group FROM '.$cT);
		if ($channels->num_rows() > 0)
		{
			$category_groups = $this->EE->db->query('SELECT group_id, group_name FROM '.$cgT);
			if ($category_groups->num_rows() > 0)
			{
				foreach ($channels->result_array() as $channel)
				{
					$channel_id    = $channel['channel_id'];
					$channel_title = $channel['channel_title'];
					$channel_cat_list[$channel_id] = array();
					$channel_cat_list[$channel_id]['channel_title']  = $channel_title;
					$channel_cat_list[$channel_id]['groups'] = array();
					$channel_cat_list[$channel_id]['groups']['0'] = '- Select Category Group -'; // reset for each channel

					$cat_group     = 'x|'.$channel['cat_group'].'|';
					foreach ($category_groups->result_array() as $category_group)
					{
						$group_id   = $category_group['group_id'];
						$group_name = $category_group['group_name'];
						if (strpos($cat_group, '|'.$group_id.'|') > 0)
						{
							$channel_cat_list[$channel_id]['groups'][$group_id] = $group_name;
						}
					}
				}
			}
		}

		// build array of field names of text and text area fields
		$cT  = $this->EE->db->dbprefix . "channels";
		$cfT = $this->EE->db->dbprefix . "channel_fields";

		$fields_by_channel = array();
		$results = $this->EE->db->query("SELECT channel_id, field_id, field_label FROM ".$cT." c JOIN ".$cfT." cf ON c.field_group=cf.group_id WHERE field_type = 'text' || field_type = 'textarea' ORDER BY channel_id, field_label");
		if ($results->num_rows() > 0)
		{
			$fields = array();
			$last_channel_id = '';
			foreach($results->result_array() as $row)
			{
				$channel_id = $row['channel_id'];
				if ($channel_id != $last_channel_id)
				{
					$fields_by_channel[$last_channel_id] = $fields;
					$fields = array('0' => ' - Select Field - ');
					$last_channel_id = $channel_id;
				}
				$field_id          = $row['field_id'];
				$field_label       = $row['field_label'];
				$fields[$field_id] = $field_label;
			}
			$fields_by_channel[$last_channel_id] = $fields;
		}
		// if channel_cat_list[channel_id] exists,
		// then add fields_by_channel[channel_id]
		// to channel_cat_list[channel_id]['channel_fields']
		foreach ($channel_cat_list as $channel_id=>$val)
		{
			$channel_cat_list[$channel_id]['channel_fields'] = array();
			if (array_key_exists($channel_id,$fields_by_channel))
			{
				$channel_cat_list[$channel_id]['channel_fields'] = $fields_by_channel[$channel_id];
			}	
		}

		// build array of category fields for each category group
		$cgT = $this->EE->db->dbprefix . "category_groups";
		$cfT = $this->EE->db->dbprefix . "category_fields";

		$cat_fields_by_cat_group = array();
		$results = $this->EE->db->query("SELECT cg.group_id, field_id, field_label FROM ".$cgT." cg JOIN ".$cfT." cf ON cg.group_id=cf.group_id");
		if ($results->num_rows() > 0)
		{
			$cat_fields = array('' => ' - Select Field - ');
			$last_group_id = '';
			foreach($results->result_array() as $row)
			{
				$group_id = $row['group_id'];
				if ($group_id != $last_group_id)
				{
					$cat_fields_by_cat_group[$last_group_id] = $cat_fields;
					$cat_fields = array('0' => ' - Select Field - ');
					$last_group_id = $group_id;
				}
				$field_id              = $row['field_id'];
				$field_label           = $row['field_label'];
				$cat_fields[$field_id] = $field_label;
			}
			$cat_fields_by_cat_group[$last_group_id] = $cat_fields;
		}
		// if channel_cat_list[channel_id]['groups'][group_id] exists
		// and cat_fields_by_cat_group[group_id] exists
		// then add cat_fields_by_cat_group[group_id]
		// to channel_cat_list[channel_id]['cat_groups'][group_id]['cat_fields']
		foreach ($channel_cat_list as $channel_id=>$channel_info)
		{
			$channel_cat_list[$channel_id]['cat_groups'] = array();
			foreach ($channel_info['groups'] as $group_id=>$group_n_fields)
			{
				if (array_key_exists($group_id, $cat_fields_by_cat_group))
				{
					$channel_cat_list[$channel_id]['cat_groups'][$group_id] = array();
					$channel_cat_list[$channel_id]['cat_groups'][$group_id]['cat_fields'] = $cat_fields_by_cat_group[$group_id];
				}
			}
		}

		// omit any channels where there are no category groups
		// or there are no text or textarea channel fields
		foreach ($channel_cat_list as $channel_id=>$channel_info)
		{
			if (sizeof($channel_info['groups']) < 2 || sizeof($channel_info['channel_fields']) < 2)
			{
				unset($channel_cat_list[$channel_id]);
			}
		}

		$vars = array();
		$vars['channel_cat_list'] = $channel_cat_list;
		$vars = array_merge($vars, $settings);

		return $this->EE->load->view('index', $vars, TRUE);

	}


	/**
	 * Save Settings
	 *
	 * This function provides a little extra processing and validation
	 * than the generic settings form.
	 *
	 * @return void
	 */
	function save_settings()
	{
		$settings            = array();
		$settings['channel'] = array();
		// get list of channels
		$chanT = $this->EE->db->dbprefix . 'channels';
		$results = $this->EE->db->query('SELECT channel_id FROM '.$chanT);
		if($results->num_rows() > 0)
		{
			$channel = '';
			$channel = $this->EE->input->post('channel');
			if (!empty($channel) && is_array($channel) )
			{
				foreach ($results->result_array() as $row)
				{
					$channel_id = $row['channel_id'];
					if (array_key_exists ($channel_id, $channel))
					{
						$remove_blanks = array();
						foreach ($channel[$channel_id]['category_group_id'] as $index=>$pair)
						{
							$settings['channel'][$channel_id][$index]['category_group_id'] = $pair;
							$settings['channel'][$channel_id][$index]['category_field_id'] = '';
							$remove_blanks[$index] = ($pair == '' || is_null($pair));
						}
						foreach ($channel[$channel_id]['text_field_id'] as $index2=>$pair2)
						{
							$settings['channel'][$channel_id][$index2]['text_field_id'] = $pair2;
							$remove_blanks[$index2] = ($remove_blanks[$index2] || ($pair2 == '' || is_null($pair2)));
						}
						if (array_key_exists ('category_field_id', $channel[$channel_id]))
						{
							foreach ($channel[$channel_id]['category_field_id'] as $index3=>$pair3)
							{
								$settings['channel'][$channel_id][$index3]['category_field_id'] = $pair3;
								$remove_blanks[$index3] = ($remove_blanks[$index3] && ($pair3 == '' || is_null($pair3)));
							}
						}
						foreach ($remove_blanks as $index=>$val)
						{
							if ($val==TRUE)
							{
								unset($settings['channel'][$channel_id][$index]);
							}
						}


						$update_existing = $this->EE->input->post('update_existing');
						if ($update_existing && array_key_exists ($channel_id, $update_existing)) {
							if ($update_existing[$channel_id] == TRUE) {
								// find all channel entries for this channel with a category
								$catPostT     = $this->EE->db->dbprefix . 'category_posts';
								$channelDataT = $this->EE->db->dbprefix . 'channel_data';
								$results = $this->EE->db->query('SELECT DISTINCT cp.entry_id FROM '.$catPostT.' cp JOIN '.$channelDataT.' cd ON cp.entry_id=cd.entry_id WHERE cd.channel_id = '.$channel_id);

								// loop through and call cat_to_text for each
								if ($results->num_rows() > 0)
								{
									foreach($results->result_array() as $row)
									{
										$this->cat_to_text_init($row['entry_id'], $settings);
									}
								}

								$this->EE->session->set_flashdata(
									'message_success',
									$this->EE->lang->line('existing_updated')
								);
							}
						}
					}
				}
			}
		}

		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
								'extensions',
								array('settings' => serialize($settings))
		);

		$this->EE->session->set_flashdata(
			'message_success',
			$this->EE->lang->line('preferences_updated')
		);

// moving to inside loop above, only update existing on a
// per channel basis
/*
		if ($this->EE->input->post('update_existing') == TRUE)
		{
			// find all channel entries with a category
			$catPostT = $this->EE->db->dbprefix . 'category_posts';
			$results = $this->EE->db->query('SELECT DISTINCT entry_id FROM '.$catPostT);

			// loop through and call cat_to_text for each
			if ($results->num_rows() > 0)
			{
				foreach($results->result_array() as $row)
				{
					$this->cat_to_text_init($row['entry_id'], $settings);
				}
			}

			$this->EE->session->set_flashdata(
				'message_success',
				$this->EE->lang->line('existing_updated')
			);	
		}
*/
	}


}
// END CLASS

/* End of file ext.categories_to_text.php */
/* Location: ./system/expressionengine/third_party/categories_to_text/ext.categories_to_text.php */