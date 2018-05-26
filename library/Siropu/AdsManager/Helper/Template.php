<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Helper_Template
{
	public static function helperCostAmountAlt($amount, $currency)
	{
		return self::helperCostAmount(array('cost_amount' => $amount, 'cost_currency' => $currency));
	}
	public static function helperCostAmount($data)
	{
		switch($data['cost_currency'])
		{
			case 'TWD':
			case 'HUF':
			case 'JPY':
			case 'IRR':
				return floatval($data['cost_amount']);
				break;
			default:
				return $data['cost_amount'];
				break;
		}
	}
	public static function helperCostCurrency($currency)
	{
		$list = array();

		if ($currencyAlt = self::_getOptions()->siropu_ads_manager_currency_code_alt)
		{
			foreach (array_filter(array_map('trim', explode("\n", $currencyAlt))) as $codeGroup)
			{
				$code = explode('=', $codeGroup);
				$list[$code[0]] = $code[1];
			}
		}

		if (isset($list[$currency]))
		{
			return $list[$currency];
		}

		return $currency;
	}
	public static function helperPositionName($hook, $positions)
	{
		if (isset($positions[$hook]))
		{
			return $positions[$hook];
		}

		if (preg_match('/[0-9]+$/', $hook, $match))
		{
			$hook = preg_replace('/[0-9]+$/', 'x', $hook);

			if (isset($positions[$hook]))
			{
				$name = $positions[$hook];
				$name = preg_replace('/\((.*?)\)/', '', $name);
				$name = preg_replace('/x/', current($match), $name);
			}
		}

		return !empty($name) ? trim($name) : 'N/A';
	}
	public static function helperCtr($views, $clicks)
	{
		return ($views && $clicks ? substr(number_format(($clicks / $views * 100), 3, '.', ''), 0, -1) : '0.00') . '%';
	}
	public static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
}