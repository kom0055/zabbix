<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


$inline_js = '';

$form = (new CForm())
	->cleanItems()
	->setId('popup.condition')
	->setName('popup.condition')
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->addVar('action', $data['action'])
	->addVar('type', $data['type']);

if (array_key_exists('source', $data)) {
	$form->addVar('source', $data['source']);
}

$condition_type = $data['last_type'];

$form_list = (new CFormList())->cleanItems();

switch ($data['type']) {
	case ZBX_POPUP_CONDITION_TYPE_EVENT_CORR:
		require_once dirname(__FILE__).'/../../include/correlation.inc.php';

		// Type select.
		$condition_type_combobox = new CComboBox(
			'condition_type',
			$condition_type,
			"reloadPopup(this.form, 'popup.event.condition.edit');",
			corrConditionTypes()
		);

		$form_list->addRow(_('Condition type'), $condition_type_combobox);

		// Old|New event tag form elements.
		if ($condition_type == ZBX_CORR_CONDITION_OLD_EVENT_TAG
				|| $condition_type == ZBX_CORR_CONDITION_NEW_EVENT_TAG) {
			$operator = (new CRadioButtonList('', CONDITION_OPERATOR_EQUAL))
				->setModern(true)
				->addValue(corrConditionOperatorToString(
					getOperatorsByCorrConditionType(ZBX_CORR_CONDITION_OLD_EVENT_TAG)[0]
				), getOperatorsByCorrConditionType(ZBX_CORR_CONDITION_OLD_EVENT_TAG)[0]);
			$new_condition_tag = (new CTextAreaFlexible('tag'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_tag->getPostJS();

			$form_list
				->addRow(_('Operator'), [$operator, new CVar('operator', CONDITION_OPERATOR_EQUAL)])
				->addRow(_('Tag'), $new_condition_tag);
		}

		// New event host group form elements.
		if ($condition_type == ZBX_CORR_CONDITION_NEW_EVENT_HOSTGROUP) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach (getOperatorsByCorrConditionType(ZBX_CORR_CONDITION_NEW_EVENT_HOSTGROUP) as $value) {
				$operator->addValue(corrConditionOperatorToString($value), $value);
			}

			$hostgroup_multiselect = (new CMultiSelect([
				'name' => 'groupids[]',
				'object_name' => 'hostGroup',
				'default_value' => 0,
				'popup' => [
					'parameters' => [
						'srctbl' => 'host_groups',
						'srcfld1' => 'groupid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'groupids_'
					]
				]
			]))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $hostgroup_multiselect->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Host groups'), $hostgroup_multiselect);
		}

		// Event tag pair form elements.
		if ($condition_type == ZBX_CORR_CONDITION_EVENT_TAG_PAIR) {
			$operator = (new CRadioButtonList('', CONDITION_OPERATOR_EQUAL))
				->setModern(true)
				->addValue(corrConditionOperatorToString(
					getOperatorsByCorrConditionType(ZBX_CORR_CONDITION_EVENT_TAG_PAIR)[0]
				), getOperatorsByCorrConditionType(ZBX_CORR_CONDITION_EVENT_TAG_PAIR)[0]);
			$new_condition_oldtag = (new CTextAreaFlexible('oldtag'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);
			$new_condition_newtag = (new CTextAreaFlexible('newtag'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_oldtag->getPostJS();
			$inline_js .= $new_condition_newtag->getPostJS();

			$form_list
				->addRow(_('Old tag'), $new_condition_oldtag)
				->addRow(_('Operator'), [$operator, new CVar('operator', CONDITION_OPERATOR_EQUAL)])
				->addRow(_('New tag'), $new_condition_oldtag);
		}

		// Old|New event tag value form elements.
		if ($condition_type == ZBX_CORR_CONDITION_OLD_EVENT_TAG_VALUE
				|| $condition_type == ZBX_CORR_CONDITION_NEW_EVENT_TAG_VALUE) {
			$combobox_options = [];
			foreach (getOperatorsByCorrConditionType($condition_type) as $value) {
				$combobox_options[$value] = corrConditionOperatorToString($value);
			}

			$operator = new CComboBox('operator', null, null, $combobox_options);
			$new_condition_tag = (new CTextAreaFlexible('tag'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);
			$new_condition_value = (new CTextAreaFlexible('value'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_tag->getPostJS();
			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Tag'), $new_condition_tag)
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}
		break;

	case ZBX_POPUP_CONDITION_TYPE_ACTION:
		require_once dirname(__FILE__).'/../../include/actions.inc.php';

		// Collect all operators options.
		$combobox_options = [];
		$action_condition_options = [];
		foreach ($data['allowed_conditions'] as $type) {
			$action_condition_options[$type] = condition_type2str($type);

			foreach (get_operators_by_conditiontype($type) as $value) {
				$combobox_options[$type][$value] = condition_operator2str($value);
			}
		}

		// Type select.
		$action_condition_type_combobox = new CComboBox(
			'condition_type',
			$condition_type,
			"reloadPopup(this.form, 'popup.action.condition.edit');",
			$action_condition_options
		);

		$form_list->addRow(_('Condition type'), $action_condition_type_combobox);

		// Trigger name form elements.
		if ($condition_type == CONDITION_TYPE_TRIGGER_NAME) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_LIKE))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_TRIGGER_NAME] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$new_condition_value = (new CTextAreaFlexible('value', ''))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Trigger form elements.
		if ($condition_type == CONDITION_TYPE_TRIGGER) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_TRIGGER] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$trigger_multiselect = (new CMultiSelect([
				'name' => 'value[]',
				'object_name' => 'triggers',
				'default_value' => 0,
				'popup' => [
					'parameters' => [
						'srctbl' => 'triggers',
						'srcfld1' => 'triggerid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'trigger_new_condition',
						'editable' => true,
						'noempty' => true
					]
				]
			]))
				->setId('trigger_new_condition')
				->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $trigger_multiselect->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Triggers'), $trigger_multiselect);
		}

		// Trigger severity form elements.
		if ($condition_type == CONDITION_TYPE_TRIGGER_SEVERITY) {
			$config = select_config();

			$severity_names = [];
			for ($severity = TRIGGER_SEVERITY_NOT_CLASSIFIED; $severity < TRIGGER_SEVERITY_COUNT; $severity++) {
				$severity_names[] = getSeverityName($severity, $config);
			}

			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_TRIGGER_SEVERITY]);
			$new_condition_value = new CComboBox('value', null, null, $severity_names);

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Severity'), $new_condition_value);
		}

		// Application form elements.
		if ($condition_type == CONDITION_TYPE_APPLICATION) {
			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_APPLICATION]);
			$new_condition_value = (new CTextAreaFlexible('value'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Host form elements.
		if ($condition_type == CONDITION_TYPE_HOST) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_HOST] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$host_multiselect = (new CMultiSelect([
				'name' => 'value[]',
				'object_name' => 'hosts',
				'default_value' => 0,
				'popup' => [
					'parameters' => [
						'srctbl' => 'hosts',
						'srcfld1' => 'hostid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'host_new_condition',
						'editable' => true
					]
				]
			]))
				->setId('host_new_condition')
				->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $host_multiselect->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Hosts'), $host_multiselect);
		}

		// Host group form elements.
		if ($condition_type == CONDITION_TYPE_HOST_GROUP) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_HOST_GROUP] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$hostgroup_multiselect = (new CMultiSelect([
				'name' => 'value[]',
				'object_name' => 'hostGroup',
				'default_value' => 0,
				'popup' => [
					'parameters' => [
						'srctbl' => 'host_groups',
						'srcfld1' => 'groupid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'hostgroup_new_condition',
						'editable' => true
					]
				]
			]))
				->setId('hostgroup_new_condition')
				->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $hostgroup_multiselect->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Host groups'), $hostgroup_multiselect);
		}

		// Problem is supressed form elements.
		if ($condition_type == CONDITION_TYPE_SUPPRESSED) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_NO))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_SUPPRESSED] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$form_list->addRow(_('Operator'), $operator);
		}

		// Tag form elements.
		if ($condition_type == CONDITION_TYPE_EVENT_TAG) {
			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_EVENT_TAG]);
			$new_condition_value = (new CTextAreaFlexible('value'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Tag'), $new_condition_value);
		}

		// Tag value form elements.
		if ($condition_type == CONDITION_TYPE_EVENT_TAG_VALUE) {
			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_EVENT_TAG_VALUE]);
			$new_condition_value2 = (new CTextAreaFlexible('value2'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);
			$new_condition_value = (new CTextAreaFlexible('value'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_value2->getPostJS();
			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Tag'), $new_condition_value2)
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Template form elements.
		if ($condition_type == CONDITION_TYPE_TEMPLATE) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_TEMPLATE] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$template_multiselect = (new CMultiSelect([
				'name' => 'value[]',
				'object_name' => 'templates',
				'default_value' => 0,
				'popup' => [
					'parameters' => [
						'srctbl' => 'templates',
						'srcfld1' => 'hostid',
						'srcfld2' => 'host',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'template_new_condition',
						'editable' => true
					]
				]
			]))
				->setId('template_new_condition')
				->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $template_multiselect->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Templates'), $template_multiselect);
		}

		// Time period form elements.
		if ($condition_type == CONDITION_TYPE_TIME_PERIOD) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_IN))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_TIME_PERIOD] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$new_condition_value = (new CTextBox('value', ZBX_DEFAULT_INTERVAL))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Discovery host ip form elements.
		if ($condition_type == CONDITION_TYPE_DHOST_IP) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_DHOST_IP] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$new_condition_value = (new CTextBox('value', '192.168.0.1-127,192.168.2.1'))
				->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Discovery check form elements.
		if ($condition_type == CONDITION_TYPE_DCHECK) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_DCHECK] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$dcheck_popup_select = [
				(new CInput('hidden', 'value', '0'))
					->removeId()
					->setId('dcheck_new_condition_value'),
				(new CTextBox('dcheck', '', true))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH),
				(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
				(new CButton('btn1', _('Select')))
					->addClass(ZBX_STYLE_BTN_GREY)
					->onClick('return PopUp("popup.generic",'.
						CJs::encodeJson([
							'srctbl' => 'dchecks',
							'srcfld1' => 'dcheckid',
							'srcfld2' => 'name',
							'dstfrm' => $form->getName(),
							'dstfld1' => 'dcheck_new_condition_value',
							'dstfld2' => 'dcheck',
							'writeonly' => '1'
						]).', null, this);'
					)
			];

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Discovery checks'), $dcheck_popup_select);
		}

		// Discovery object form elements.
		if ($condition_type == CONDITION_TYPE_DOBJECT) {
			$dobject_options = [];
			foreach ([EVENT_OBJECT_DHOST, EVENT_OBJECT_DSERVICE] as $object) {
				$dobject_options[$object] = discovery_object2str($object);
			}

			$operator = (new CRadioButtonList('', CONDITION_OPERATOR_EQUAL))
				->setModern(true)
				->addValue(
					$combobox_options[CONDITION_TYPE_DOBJECT][CONDITION_OPERATOR_EQUAL],
					CONDITION_OPERATOR_EQUAL
				);
			$new_condition_value = new CComboBox('value', null, null, $dobject_options);

			$form_list
				->addRow(_('Operator'), [$operator, new CVar('operator', CONDITION_OPERATOR_EQUAL)])
				->addRow(_('Discovery object'), $new_condition_value);
		}

		// Discovery rule form elements.
		if ($condition_type == CONDITION_TYPE_DRULE) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_DRULE] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$drule_multiselect = (new CMultiSelect([
				'name' => 'value[]',
				'object_name' => 'drules',
				'default_value' => 0,
				'popup' => [
					'parameters' => [
						'srctbl' => 'drules',
						'srcfld1' => 'druleid',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'drule_new_condition'
					]
				]
			]))
				->setId('drule_new_condition')
				->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $drule_multiselect->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Discovery rules'), $drule_multiselect);
		}

		// Discovery status form elements.
		if ($condition_type == CONDITION_TYPE_DSTATUS) {
			$dstatus_options = [];
			foreach ([DOBJECT_STATUS_UP, DOBJECT_STATUS_DOWN, DOBJECT_STATUS_DISCOVER, DOBJECT_STATUS_LOST] as $stat) {
				$dstatus_options[$stat] = discovery_object_status2str($stat);
			}

			$operator = (new CRadioButtonList('', CONDITION_OPERATOR_EQUAL))
				->setModern(true)
				->addValue(
					$combobox_options[CONDITION_TYPE_DSTATUS][CONDITION_OPERATOR_EQUAL],
					CONDITION_OPERATOR_EQUAL
				);
			$new_condition_value = new CComboBox('value', null, null, $dstatus_options);

			$form_list
				->addRow(_('Operator'), [$operator, new CVar('operator', CONDITION_OPERATOR_EQUAL)])
				->addRow(_('Status'), $new_condition_value);
		}

		// Proxy form elements.
		if ($condition_type == CONDITION_TYPE_PROXY) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_PROXY] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$proxy_multiselect = (new CMultiSelect([
				'name' => 'value',
				'object_name' => 'proxies',
				'multiple' => false,
				'default_value' => 0,
				'popup' => [
					'parameters' => [
						'srctbl' => 'proxies',
						'srcfld1' => 'proxyid',
						'srcfld2' => 'host',
						'dstfrm' => $form->getName(),
						'dstfld1' => 'proxy_new_condition'
					]
				]
			]))
				->setId('proxy_new_condition')
				->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $proxy_multiselect->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Proxies'), $proxy_multiselect);
		}

		// Received value form elements.
		if ($condition_type == CONDITION_TYPE_DVALUE) {
			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_DVALUE]);
			$new_condition_value = (new CTextAreaFlexible('value'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Service port form elements.
		if ($condition_type == CONDITION_TYPE_DSERVICE_PORT) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_DSERVICE_PORT] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$new_condition_value = (new CTextBox('value', '0-1023,1024-49151'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Service type form elements.
		if ($condition_type == CONDITION_TYPE_DSERVICE_TYPE) {
			$operator = (new CRadioButtonList('operator', CONDITION_OPERATOR_EQUAL))->setModern(true);
			foreach ($combobox_options[CONDITION_TYPE_DSERVICE_TYPE] as $key => $value) {
				$operator->addValue($value, $key);
			}

			$discovery_check_types = discovery_check_type2str();
			order_result($discovery_check_types);

			$new_condition_value = new CComboBox('value', null, null, $discovery_check_types);

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Service type'), $new_condition_value);
		}

		// Discovery uptime|downtime form elements.
		if ($condition_type == CONDITION_TYPE_DUPTIME) {
			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_DUPTIME]);
			$new_condition_value = (new CNumericBox('value', 600, 15))->setWidth(ZBX_TEXTAREA_NUMERIC_BIG_WIDTH);

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Host name form elements.
		if ($condition_type == CONDITION_TYPE_HOST_NAME) {
			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_HOST_NAME]);
			$new_condition_value = (new CTextAreaFlexible('value'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Host metadata form elements.
		if ($condition_type == CONDITION_TYPE_HOST_METADATA) {
			$operator = new CComboBox('operator', null, null, $combobox_options[CONDITION_TYPE_HOST_METADATA]);
			$new_condition_value = (new CTextAreaFlexible('value'))->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

			$inline_js .= $new_condition_value->getPostJS();

			$form_list
				->addRow(_('Operator'), $operator)
				->addRow(_('Value'), $new_condition_value);
		}

		// Event type form elements.
		if ($condition_type == CONDITION_TYPE_EVENT_TYPE) {
			$operator = (new CRadioButtonList('', CONDITION_OPERATOR_EQUAL))
				->setModern(true)
				->addValue($combobox_options[CONDITION_TYPE_EVENT_TYPE][CONDITION_OPERATOR_EQUAL],
					CONDITION_OPERATOR_EQUAL);
			$new_condition_value = new CComboBox('value', null, null, eventType());

			$form_list
				->addRow(_('Operator'), [$operator, new CVar('operator', CONDITION_OPERATOR_EQUAL)])
				->addRow(_('Event types'), $new_condition_value);
		}
		break;

	case ZBX_POPUP_CONDITION_TYPE_ACTION_OPERATION:
		require_once dirname(__FILE__).'/../../include/actions.inc.php';

		// Collect all options for combobox.
		$combobox_options = [];
		foreach ($data['allowed_conditions'] as $type) {
			$combobox_options[$type] = condition_type2str($type);
		}

		// Type select.
		$opcondition_type_combobox = new CComboBox(
			'condition_type',
			$condition_type,
			"reloadPopup(this.form, 'popup.condition.operations');",
			$combobox_options
		);

		$form_list->addRow(_('Condition type'), $opcondition_type_combobox);

		// Acknowledge form elements.
		$operators_options = [];
		foreach (get_operators_by_conditiontype(CONDITION_TYPE_EVENT_ACKNOWLEDGED) as $type) {
			$operators_options[$type] = condition_operator2str($type);
		}

		$operator = (new CRadioButtonList('', CONDITION_OPERATOR_EQUAL))
			->setModern(true)
			->addValue(condition_operator2str(CONDITION_OPERATOR_EQUAL), CONDITION_OPERATOR_EQUAL);

		$condition_value = new CComboBox('value', '', null, [
			EVENT_NOT_ACKNOWLEDGED => _('Not Ack'),
			EVENT_ACKNOWLEDGED => _('Ack')
		]);

		$form_list
			->addRow(_('Operator'), [$operator, new CVar('operator', CONDITION_OPERATOR_EQUAL)])
			->addRow(_('Acknowledge'), $condition_value);
		break;
}

$form->addItem($form_list);

$output = [
	'header' => $data['title'],
	'script_inline' => $inline_js,
	'body' => $form->toString(),
	'buttons' => [
		[
			'title' => _('Add'),
			'class' => '',
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'return validateConditionPopup();'
		]
	]
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo (new CJson())->encode($output);
