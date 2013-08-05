<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=categories_to_text');?>

<script>
	$(document).ready(function() {

		var makeOptions = function ($arr, $selection) {
			$htmlOptions = '';
			$.each($arr, function($key, $value) {
				$htmlOptions = $htmlOptions+'<option value="'+$key+'"';
				if ($key == $selection)
				{
					$htmlOptions = $htmlOptions+' selected="selected"';
				}
				$htmlOptions = $htmlOptions+'>'+$value+'</option>';
			});
			return $htmlOptions;
		}

		var updateCatTextFieldList = function(event) {
			$catGroupId  = $(event.target).val();
			$thisName   = $(event.target).attr('name');
			$firstObPos = $thisName.indexOf('[');
			$firstCbPos = $thisName.indexOf(']');
			$channelId  = $thisName.substr($firstObPos+1, $firstCbPos-$firstObPos-1);

			$lastObPos = $thisName.lastIndexOf('[');
			$lastCbPos = $thisName.lastIndexOf(']');
			$pairNum   = $thisName.substr($lastObPos+1, $lastCbPos-$lastObPos-1);

			$channelInfo = JSON.parse($(event.target).parent().parent().parent().parent().prev().html());
			if ($catGroupId in $channelInfo['cat_groups'] &&
				'cat_fields' in $channelInfo['cat_groups'][$catGroupId]) {
				// the array index exists
				$catFieldsByCatGroup = $channelInfo['cat_groups'][$catGroupId]['cat_fields'];
			} else {
				$catFieldsByCatGroup = '';
			}
			$synTextFieldSel = makeOptions($catFieldsByCatGroup, '');

			// sort $catFieldsByCatGroup first, otherwise the '' option
			// ends up last (for some reason)
			var $groupsSorted = [];
			for (var x in $catFieldsByCatGroup) {
				var xNum = parseInt(x) || "0";
				$catFieldsByCatGroup.hasOwnProperty(x) && $groupsSorted.push(xNum);
			}
			$groupsSorted.sort(function(a,b){
				var aI = parseInt(a) || 0;
				var bI = parseInt(b) || 0;
				return aI>bI ? 1 : aI<bI ? -1 : 0;				
			});
			
			var catFieldCount = 0;
			$newSelectOptHtml = '';
			for (var i=0; i<$groupsSorted.length; i++) {
				var $html = '<option value="' + $groupsSorted[i] + '"';
				if ($groupsSorted[i] == '')
				{
					$html = $html + ' selected="selected"';
				}
				$newSelectOptHtml = $newSelectOptHtml + $html + '>' + $catFieldsByCatGroup[$groupsSorted[i]] + '</option>';
			}
			if ($groupsSorted.length <= 1) {
				$newSelectHtml = '<select style="display: none;" class="catFieldId" name="channel['+$channelId+'][category_field_id]['+$pairNum+']"><option value=""></option></select><?php echo(lang("no_cat_text_fields")); ?>';
			} else {
				$newSelectHtml = '<select class="catFieldId" name="channel['+$channelId+'][category_field_id]['+$pairNum+']">'+$newSelectOptHtml+'</select>';
			}
			$(event.target).parent().next().next().html($newSelectHtml);
		}

		$('.catGroupId').change(updateCatTextFieldList);

		var addLineHtml = function($pairNum, $channelId, $catGroupSel, $textFieldSel, $synTextFieldSel) {
			$htmlStr = '<tr class="odd"><td><label for="pair_'+$pairNum+'">Pair '+$pairNum+'</label></td><td><select class="catGroupId" name="channel['+$channelId+'][category_group_id]['+$pairNum+']">'+$catGroupSel+'</select></td><td><select class="textFieldId" name="channel['+$channelId+'][text_field_id]['+$pairNum+']">'+$textFieldSel+'</select></td><td>'+$synTextFieldSel+'</td><td><div class="deletePairRow" style="Cursor:pointer">Delete</div></td></tr>';
			return $htmlStr;
		}

		var addnewCFPLine = function(event) {
			$channelId = parseInt($(event.target).parent().next().children().first().html());
			$pairNum = parseInt($(event.target).parent().next().next().children().first().html());
			$channelInfo = JSON.parse($('#channel_'+$channelId).html());
			$channelGroups = $channelInfo['groups'];
			$channelFields = $channelInfo['channel_fields'];
			$catGroups     = $channelInfo['cat_groups'];

			$catGroupSel     = makeOptions($channelGroups, '0');
			$textFieldSel    = makeOptions($channelFields, '0');
			$synTextFieldSel = '<select style="display: none;" class="catFieldId" name="channel['+$channelId+'][category_field_id]['+$pairNum+']"><option value=""></option></select><?php echo(lang("no_cat_text_fields")); ?>';

			$htmlToAdd = addLineHtml($pairNum, $channelId, $catGroupSel, $textFieldSel, $synTextFieldSel);

			if ($pairNum == 1)
			{
				$bottomLine = $(event.target).parent().parent();
				$bottomLine.before($htmlToAdd);
				$bottomLine.attr('class', 'even');
				$pairNum = parseInt($bottomLine.children().first().next().next().next().children().first().html());
			} else {
				$lastLine = $(event.target).parent().parent().prev();
				$lastLine.after($htmlToAdd);

				if ($lastLine.hasClass('even')) {
					$lastLine.next().attr('class', 'odd').next().attr('class', 'even');
				} else {
					$lastLine.next().attr('class', 'even').next().attr('class', 'odd');
				}
			}

			$('.addnewCFP').unbind('click').click(addnewCFPLine);
			$('.deletePairRow').unbind('click').click(deleteCFPLine);
			$('.catGroupId').unbind('change').change(updateCatTextFieldList);

			$nextPairNum = $pairNum + 1;
			$(event.target).parent().next().next().children().first().html($nextPairNum);

		};

		$('.addnewCFP').click(addnewCFPLine);

		var deleteCFPLine = function(event) {
			var $thisLine      = $(event.target).parent().parent();
			var $delLineParent = $thisLine.parent();
			var $channelId     = $thisLine.siblings().last().children().first().next().next().children().first().html();

			$thisLine.remove();
			$('.addnewCFP').unbind('click').click(addnewCFPLine);
			$('#channel_'+$channelId).next().children().last().children().each( function($indx) {
				var $thisLn = $(this);
				if ( $indx % 2 === 0 ) {
					$thisLn.attr('class', 'odd');
				} else {
					$thisLn.attr('class', 'even');
				}
				var $newNum = $indx + 1;
				$('#nextFor-'+$channelId).html($newNum);
				$thisLn.children().first().children().first().html('Pair '+$newNum);

				var $name1 = $thisLn.children().first().next().children().first().attr('name');
				var $bracketPos = $name1.lastIndexOf('[');
				var $name1new = $name1.substr(0, $bracketPos+1) + $newNum + ']';
				$thisLn.children().first().next().children().first().attr('name', $name1new);

				var $name2 = $thisLn.children().first().next().next().children().first().attr('name');
				$bracketPos = $name2.lastIndexOf('[');
				var $name2new = $name2.substr(0, $bracketPos+1) + $newNum + ']';
				$thisLn.children().first().next().next().children().first().attr('name', $name2new);

				var $name3 = $thisLn.children().first().next().next().next().children().first().attr('name');
				$bracketPos = $name3.lastIndexOf('[');
				var $name3new = $name3.substr(0, $bracketPos+1) + $newNum + ']';
				$thisLn.children().first().next().next().next().children().first().attr('name', $name3new);
			});
		}

		$('.deletePairRow').click(deleteCFPLine);

	});
</script>

<?php

function make_options ($arr, $selection)
{
	$html = '';
	foreach ($arr as $key => $val)
	{
		$html.= '<option value="'.$key.'"';
		if ($key == $selection)
		{
			$html.= ' selected="selected"';
		}
		$html.= '>'.$val.'</option>';
	}
	return $html;
}

foreach ($channel_cat_list as $channel_id=>$channel_info)
{
	$num            = 0;
	$channel_title  = $channel_info['channel_title'];
	$channel_groups = $channel_info['groups'];
	$channel_fields = $channel_info['channel_fields'];
	$cat_groups     = $channel_info['cat_groups'];

	echo ('<h2>channel: '.$channel_title.'</h2>');
	echo ('<div></div>');
	echo ('<div id="channel_'.$channel_id.'" style="display: none;">'.json_encode($channel_info).'</div>');
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading('', lang('category_group'), lang('text_field'), lang('syn_cat_text_field'), '');

	if (array_key_exists ($channel_id, $channel))
	{
		if (!empty($channel[$channel_id]))
		{
			foreach ($channel[$channel_id] as $num=>$pair)
			{
				$category_group_id = $pair['category_group_id'];
				$text_field_id     = $pair['text_field_id'];
				$category_field_id = $pair['category_field_id'];

				$cat_groups_html = make_options($channel_groups, $category_group_id);
				if (!array_key_exists ($text_field_id, $channel_fields))
				{
					$fields_html = '<option selected="selected" value="0">- Select Field -</option>';
				} else {
					$fields_html = make_options($channel_fields, $text_field_id);
				}
				if (!array_key_exists ($category_group_id, $cat_groups))
				{
					$cat_fields_sel_html = '<select style="display: none;" class="catFieldId" name="channel['.$channel_id.'][category_field_id]['.$num.']"><option value=""></option></select>'.lang("no_cat_text_fields");
				} else {
					$clean_array = array_filter($cat_groups[$category_group_id]['cat_fields']);
					if (empty($clean_array))
					{
						$cat_fields_sel_html = '<select style="display: none;" class="catFieldId" name="channel['.$channel_id.'][category_field_id]['.$num.']"><option value=""></option></select>'.lang("no_cat_text_fields");
					} else {
						$cat_fields_html   = make_options($cat_groups[$category_group_id]['cat_fields'], $category_field_id);
					$cat_fields_sel_html = '<select class="catFieldId" name="channel['.$channel_id.'][category_field_id]['.$num.']">'.$cat_fields_html.'</select>';
					}
				}

				$this->table->add_row(
					lang('pair_'.$num, 'pair_'.$num), 
					'<select class="catGroupId" name="channel['.$channel_id.'][category_group_id]['.$num.']">'.$cat_groups_html.'</select>',
					'<select class="textFieldId" name="channel['.$channel_id.'][text_field_id]['.$num.']">'.$fields_html.'</select>',
					$cat_fields_sel_html,
					'<div class="deletePairRow" style="Cursor:pointer">Delete</div>'
				);
			}
		}
	}

	// add button to add new category-field pair for this channel
	$nextNum = $num + 1;
	$this->table->add_row('', '<div class="addnewCFP" style="Cursor:pointer">Add New Category-Field Pair</div>', '<div style="display: none;">'.$channel_id.'</div>', '<div id="nextFor-'.$channel_id.'" style="display: none;">'.$nextNum.'</div>','');

	echo $this->table->generate();
	echo '<input type="checkbox" name="update_existing['.$channel_id.']" value="TRUE">&nbsp;Update Existing '.$channel_title.' Channel Entries<br /><br />';
}

//echo '<input type="checkbox" name="update_existing" value="TRUE">&nbsp;Update Existing Channel Entries<br /><br />';
echo form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'));

$this->table->clear();
echo form_close();

/* End of file index.php */
/* Location: ./system/expressionengine/third_party/categories_to_text/views/index.php */
