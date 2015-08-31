<?php
/**
 * 
 * This file is a part of PostMarkPlugin.
 *
 * PostMarkPlugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * PostMarkPlugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * @category  phplist
 * @package   PostMarkPlugin
 * @author    Jean-Philippe Bayard
 * @copyright 2015 Jean-Philippe Bayard
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
*/

	function gethostbyname6($host, $try_a = false) {
        // get AAAA record for $host
        // if $try_a is true, if AAAA fails, it tries for A
        // the first match found is returned
        // otherwise returns false

        $dns = gethostbynamel6($host, $try_a);
        if ($dns == false) { return false; }
        else { return $dns[0]; }
    }

    function gethostbynamel6($host, $try_a = false) {
        // get AAAA records for $host,
        // if $try_a is true, if AAAA fails, it tries for A
        // results are returned in an array of ips found matching type
        // otherwise returns false

        $dns6 = dns_get_record($host, DNS_AAAA);
        if ($try_a == true) {
            $dns4 = dns_get_record($host, DNS_A);
            $dns = array_merge($dns4, $dns6);
        }
        else { $dns = $dns6; }
        $ip6 = array();
        $ip4 = array();
        foreach ($dns as $record) {
            if ($record["type"] == "A") {
                $ip4[] = $record["ip"];
            }
            if ($record["type"] == "AAAA") {
                $ip6[] = $record["ipv6"];
            }
        }
        if (count($ip6) < 1) {
            if ($try_a == true) {
                if (count($ip4) < 1) {
                    return false;
                }
                else {
                    return $ip4;
                }
            }
            else {
                return false;
            }
        }
        else {
            return $ip6;
        }
    }

	function addRecord($array, $newrecord )
	{
		
		
		$remoteIP = gethostbyaddr($newrecord['ip']);
		if (strstr($remoteIP, ', ')) {
			$ips = explode(', ', $remoteIP);
			$remoteIP = $ips[0];
		}
		
		$host =  explode(".", $remoteIP);
		$newrecord['domain'] =  $host[count($host)-2].'.'.$host[count($host)-1];

		
		if (count($array)> 0)
		{
			for ( $o = 0; $o < count($array) ; $o++)
			{
				$subarray = $array[$o];
				If  ( ($subarray['domain'] == $newrecord['domain']) && ($subarray['ip'] == $newrecord['ip']) && ( $subarray['source_ip_version'] == $newrecord['source_ip_version']) )
				{
					$array[$o]['spf_pass'] += $newrecord['spf_pass'];
					$array[$o]['spf_failed'] += $newrecord['spf_failed'];
					$array[$o]['dkim_pass'] += $newrecord['dkim_pass'];
					$array[$o]['dkim_failed'] += $newrecord['dkim_failed'];
					
					return $array;
				}
			}
			
			$array[] = $newrecord;
			return $array;
		}
		else
		{
			$array[] = $newrecord;
			return $array;
		}
		
	}
	
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$start = $time;
	
	if (!isSet( $_GET['private_key'] ) )
	{
		echo '<strong> Error : You must set your private key first </strong>';
		echo 'You can setup your dmarc record according to Postmark by registering your domain on <a href="http://dmarc.postmarkapp.com/">Postmark website</a>';
		exit();
	}

	$private_key = $_GET['private_key'];
	
	if ($private_key == "your_private_key")
	{
		echo '<strong> Error : You must set your private key first </strong>';
		echo 'You can setup your dmarc record according to Postmark by registering your domain on <a href="http://dmarc.postmarkapp.com/">Postmark website</a>';
		exit();
	}
	
	$source_domain = $_SERVER['SERVER_NAME'];
	$source_ip = $ip = gethostbyname($source_domain);
	$source_ip_ipv6  = gethostbyname6($source_domain);
	
	if (isSet($_GET['from_date']))
		$from_date = $_GET['from_date'];
	else
	{
		$date = strtotime("-7 day" . date("Y-m-d"));
		$from_date = date("Y-m-d", $date);
	}
		
	if (isSet($_GET['to_date']))
		$to_date = $_GET['to_date'];
	else
		$to_date = date("Y-m-d");
	
	$remote_url = 'https://dmarc.postmarkapp.com';
	$reports_url = '/records/'.$private_key.'/reports?from_date='.$from_date."&to_date=".$to_date.'&limit=50';
	$report_url = '/records/'.$private_key.'/reports/';
	
	$cUrl = curl_init($remote_url . $reports_url); 
	curl_setopt($cUrl, CURLOPT_CUSTOMREQUEST, "GET"); 
	curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, true); 
	curl_setopt($cUrl, CURLOPT_HTTPHEADER, array( 'Accept: application/json' ) );
	$result = curl_exec($cUrl);
	
	if ( $result == 'Resource Not Found' )
	{
		echo '<strong> Error : Resources not found. ( Your private key may be incorrect ? ). </strong>';
		exit();
	}	
	
	$reports = json_decode($result);
	
	$aReports = array();
	for ( $i = 0 ;  $i < count($reports->entries) ; $i ++ )
	{
		$aReports [] = $reports->entries[$i]->id;
	}
	
	$aReportsDetail = array();
	$processed = 0;
	$notconfigured = 0;
	$aligned = 0;
	$alignedipv6 = 0; 
	$failed = 0;
	$threats = 0;
	$aError = array();
	$aNotConfig = array();
	$allRecord = array();
	
	$aMain = array();
	$aMain['domain'] = $source_domain;
	$aMain['ip'] = $source_ip;
	$aMain['ipv6'] = $source_ip_ipv6;
	$aMain['spf_pass'] = 0;
	$aMain['spf_failed'] = 0;
	$aMain['dkim_pass'] = 0;
	$aMain['dkim_failed'] = 0;
	
	for ( $i = 0 ;  $i < count($aReports) ; $i ++ )
	{
		curl_setopt($cUrl,CURLOPT_URL,$remote_url . $report_url . $aReports[$i]); 
		$data = curl_exec($cUrl);
		$report = json_decode($data);
		
		for ($j = 0; $j < count($report->records); $j++)
		{
			$record = $report->records[$j];
			$processed += $record->count;
			
			if ( ( $record->dkim_domain == $source_domain) && ( $record->dkim_result == 'pass') )
			{
				if ( ( $record->spf_domain == $source_domain) && ( $record->spf_result == 'pass' ) )
				{
					if ($record->source_ip == $source_ip || $record->source_ip == $source_ip_ipv6 )
					{
						if ($record->source_ip == $source_ip)
							$aligned += $record->count;
						else
							$alignedipv6 += $record->count;
						
						$aMain['spf_pass'] += $record->count;
						$aMain['dkim_pass'] += $record->count;
						
					}
					else
					{
						$threats += $record->count;
						$newrecord = array();
						$newrecord['domain'] = $source_domain;
						$newrecord['ip'] = $record->source_ip;
						$newrecord['source_ip_version'] = $record->source_ip_version;
						$newrecord['spfdomain'] = $record->spf_domain;
						$newrecord['spf_pass'] = $record->count;
						$newrecord['spf_failed'] = 0;
						$newrecord['dkim_pass'] = $record->count;
						$newrecord['dkim_failed'] = 0;
						
						$aError = addRecord( $aError, $newrecord);
					}
				}
				else
				{
					if ($record->source_ip == $source_ip  || $record->source_ip == $source_ip_ipv6)
					{
						$notconfigured += $record->count;
						$newrecord = array();
						$newrecord['domain'] = $record->header_from;
						$newrecord['ip'] = $record->source_ip;
						$newrecord['source_ip_version'] = $record->source_ip_version;
						$newrecord['spfdomain'] = $record->spf_domain;
						$newrecord['spf_pass'] = 0;
						$newrecord['spf_failed'] = $record->count;
						$newrecord['dkim_pass'] = $record->count;
						$newrecord['dkim_failed'] = 0;
						
						$aNotConfig = addRecord( $aNotConfig, $newrecord);
					}
					else
					{
						$threats += $record->count;
						$newrecord = array();
						$newrecord['domain'] = $record->spf_domain;
						$newrecord['ip'] = $record->source_ip;
						$newrecord['source_ip_version'] = $record->source_ip_version;
						$newrecord['spfdomain'] = $record->spf_domain;
						$newrecord['spf_pass'] = 0;
						$newrecord['spf_failed'] = $record->count;
						$newrecord['dkim_pass'] = $record->count;
						$newrecord['dkim_failed'] = 0;
						
						$aError = addRecord( $aError, $newrecord);
					}
				}
			}
			
			else
			{
				if ( ( $record->spf_domain == $source_domain) && ( $record->spf_result == 'pass' ) )
				{
					if ($record->source_ip == $source_ip  || $record->source_ip == $source_ip_ipv6)
					{
						$notconfigured += $record->count;
						
						$newrecord = array();
						$newrecord['domain'] = $record->header_from;
						$newrecord['ip'] = $record->source_ip;
						$newrecord['source_ip_version'] = $record->source_ip_version;
						$newrecord['spfdomain'] = $record->spf_domain;
						$newrecord['spf_pass'] = $record->count;
						$newrecord['spf_failed'] = 0;
						$newrecord['dkim_pass'] = 0;
						$newrecord['dkim_failed'] = $record->count;
						
						$aNotConfig = addRecord( $aNotConfig, $newrecord);
					}
					else
					{
						$threats += $record->count;
					
						$newrecord = array();
						$newrecord['domain'] = $record->spf_domain;
						$newrecord['ip'] = $record->source_ip;
						$newrecord['source_ip_version'] = $record->source_ip_version;
						$newrecord['spfdomain'] = $record->spf_domain;
						$newrecord['spf_pass'] = $record->count;
						$newrecord['spf_failed'] = 0;
						$newrecord['dkim_pass'] = 0;
						$newrecord['dkim_failed'] = $record->count;
						
						$aError = addRecord( $aError, $newrecord);
					}
				}
				else
				{
					if ($record->source_ip == $source_ip  || $record->source_ip == $source_ip_ipv6)
					{
						$notconfigured += $record->count;
						
						$newrecord = array();
						$newrecord['domain'] = $record->header_from;
						$newrecord['ip'] = $record->source_ip;
						$newrecord['source_ip_version'] = $record->source_ip_version;
						$newrecord['spfdomain'] = $record->spf_domain;
						$newrecord['spf_pass'] = 0;
						$newrecord['spf_failed'] = $record->count;
						$newrecord['dkim_pass'] = 0;
						$newrecord['dkim_failed'] = $record->count;
						
						$aNotConfig = addRecord( $aNotConfig, $newrecord);
					}
					else
					{
						$failed += $record->count;
					
						//Pas notre IP
						$newrecord = array();
						$newrecord['domain'] = $record->spf_domain;
						$newrecord['ip'] = $record->source_ip;
						$newrecord['source_ip_version'] = $record->source_ip_version;
						$newrecord['spfdomain'] = $record->spf_domain;
						$newrecord['spf_pass'] = 0;
						$newrecord['spf_failed'] = $record->count;
						$newrecord['dkim_pass'] = 0;
						$newrecord['dkim_failed'] = $record->count;
						
						$aError = addRecord( $aError, $newrecord);
					}
				}
			}
		}
	}
	curl_close($cUrl);
	
	usort($aError, function($a, $b) 
	{
		return strcmp( $a['domain'], $b['domain']);
	});

	
	if ( !IsSet($_GET['plugin_path']) || $_GET['plugin_path'] == "" )
		$pluginPath = './plugins/PostMarkPlugin/';
	else
		$pluginPath = $_GET['plugin_path'];
?>

<div width="600px" style="margin: 0; padding: 0; background-color: #282C2F; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
  <table style="margin: 0; border: 0; padding: 0; background-color: #282C2F;" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center" width="100%">
        <table cellspacing="0" cellpadding="0">

          <!-- Page -->
		  <tr>
            <td style="border-collapse: collapse;" width="100%">
              <table style="background-color: #fff;border-radius: 5px 5px 0 0;-webkit-border-radius: 5px 5px 0 0;-moz-border-radius: 5px 5px 0 0;" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding: 30px 22px 0;border-collapse: collapse;" align="left" width="100%">

                    <table width="550px" style="padding: 0 8px;" cellspacing="0" cellpadding="0">
                      <tr>
                        <td style="border-collapse: collapse;" width="100%">
                          <!-- Title -->
                          <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td valign="top" width="476px" style="border-collapse: collapse;">
                                <h1 style="margin: 0 0 0.6em; padding: 0; color: #222; font: bold 21px 'Helvetica Neue', Helvetica, Arial, sans-serif;">DMARC results</h1>
                              </td>
                              <td valign="top" width="204px" style="border-collapse: collapse;">
                                <a target="_blank" href="http://dmarc.postmarkapp.com/" style="color: #222;">
                                  <img align="right" border="0" alt="Postmark" class="pm-logo" width="130" src="<?php echo $pluginPath; ?>logo-postmark.png">
                                </a>
                              </td>
                            </tr>
                          </table>

                          <p style="margin: 0; padding: 0; color: #888; font: normal 18px 'Helvetica Neue', Helvetica, Aur rial, sans-serif; margin-bottom: 25px;" class="pm-meta-label">
                          <span class="pm-date-range"><?php echo $from_date." – ".$to_date ; ?></span>
                          <span style="padding: 0 8px; color: #e4e4e4;" class="pm-bull">•</span>
                          <a target="_blank" style="color: #888;" href="http://<?php echo $aMain['domain'] ?>" class="pm-domain"><?php echo $aMain['domain'] ?></a>
                          </p>
                          <!-- DMARC Counts -->
						  <table style="border-radius: 4px;border: 2px solid #e4e4e4;text-align: center;-webkit-border-radius: 5px;-moz-border-radius: 5px;" width="100%" cellpadding="0" cellspacing="0" class="pm-dmarc-totals">
                            <tr>
                              <td style="text-align: center; padding: 10px 0 15px;border-collapse: collapse;" width="224px" class="col-l">
                                <h3 style="font: bold 35px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0 auto; position: relative; display: inline; color: #88D4E3;"><?php echo $processed; ?></h3>
                                <p style="margin: 3px 0 0; padding: 0; color: #888; font: normal 13px/16px 'Helvetica Neue', Helvetica, Arial, sans-serif;">Processed</p>
                              </td>
                              <td style="text-align: center; border-left: 1px solid #e4e4e4;border-right: 1px solid #e4e4e4;padding: 10px 0 15px;border-collapse: collapse;" width="224px">
                                <h3 style="font: bold 35px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0 auto; color: #8ED49C; position: relative; display: inline;"><?php echo ($aligned+$alignedipv6); ?></h3>
                                <p style="margin: 3px 0 0; padding: 0; color: #888; font: normal 13px/16px 'Helvetica Neue', Helvetica, Arial, sans-serif;">
                                  <span style="font: bold 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #8ED49C; margin-right: 5px;"><?php echo round( 100*(($aligned+$alignedipv6)/$processed), 2); ?>%</span>Fully Aligned</p>
                              </td>
                              <td style="text-align: center; padding: 10px 0 15px;border-collapse: collapse;" width="224px" class="col-r">
                                <h3 style="font: bold 35px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0 auto; color: #E6735C; position: relative; display: inline;"><?php echo ($failed); ?></h3>
                                <p style="margin: 3px 0 0; padding: 0; color: #888; font: normal 13px/16px 'Helvetica Neue', Helvetica, Arial, sans-serif;">
                                  <span style="font: bold 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #E6735C; margin-right: 5px;"><?php echo round( 100*(($failed)/$processed), 2); ?>%</span>Failed</p>
                              </td>
                            </tr>
                          </table>
                        </td><td style="border-collapse: collapse;">
                      </td></tr>
                    </table>
                    <!-- Trusted sources -->
					
					<div style="margin: 40px 0;" class="pm-content-section pm-content-section--trusted">
                      <h2 style="margin: 0 8px 0.5em; padding: 0; color: #222; font: bold 20px 'Helvetica Neue', Helvetica, Arial, sans-serif;">Trusted Sources
                        <span style="font: normal 18px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding-left: 5px; color: #8ED49C;"><?php echo $aligned+$alignedipv6+$notconfigured; ?></span>
                      </h2>

                      <p style="margin: 0 8px 15px; padding: 0; color: #888; font: normal 13px/21px 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align: justify;" class="pm-section-body">Trusted sources are servers that have passed DKIM, SPF or both when validated against your DNS records. To be fully DMARC compliant, both SPF and DKIM must be fully aligned according to your DMARC policy.</p>

                      <table style="margin: 0;" cellspacing="0" cellpadding="0" width="100%" ><tbody>
                          <!-- Headers -->
                          
                          <!-- Results -->
                          
                         <tr>
                            <th style="text-align: left; font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0 0.8em 8px; border-bottom: 2px solid #e4e4e4;" valign="bottom" width="340px"><a id="trusted-0" style="font: bold 14px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; word-break: break-all; text-align: left; text-decoration: none; color: #222;" href="http://<?php echo $aMain['domain'] ?>"><?php echo $aMain['domain'] ?></a></th>
                            <th style="text-align: center; font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0; border-bottom: 2px solid #e4e4e4; color: #888;" valign="top" width="68px">Total</th>
                            <th style="text-align: center; font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0 0; border-bottom: 2px solid #e4e4e4; color: #888;" colspan="2" width="136px">SPF
                              <table style="text-align: center; padding-top: 5px; font-size: 9px;" cellspacing="0" cellpadding="0" width="136px">
                                <tr>
                                  <td style="text-align: center; color: #8ED49C;border-right: 1px dotted #e4e4e4;padding: 4px 0;border-collapse: collapse;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="8" width="11" border="0"></td>
                                  <td style="text-align: center; color: #E6735C;padding: 4px 0;border-collapse: collapse;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-cross.png" alt="" height="8" width="8" border="0"></td>
                                </tr>
                              </table>
                            </th>
                            <th style="font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0 0; border-bottom: 2px solid #e4e4e4; color: #888;" colspan="2" width="136px">DKIM
                              <table style="text-align: center; padding-top: 5px; font-size: 9px;" cellspacing="0" cellpadding="0" width="136px">
                                <tr>
                                  <td style="text-align: center; color: #8ED49C;border-right: 1px dotted #e4e4e4;padding: 4px 0;border-collapse: collapse;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="8" width="11" border="0"></td>
                                  <td style="text-align: center; color: #E6735C;padding: 4px 0;border-collapse: collapse;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-cross.png" alt="" height="8" width="8" border="0"></td>
                                </tr>
                              </table>
                            </th>
                          </tr>
						  
						  <?php  if ($aligned > 0) 
								 {
						  ?> 
						  <tr>
                            <td style="padding: 0.5em 0 0.5em 8px;background-color: #EFFCF2;border-collapse: collapse;" width="50%">
                              <a style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C; word-break: break-all; text-decoration: none;" class="ip-address" target="_blank" href="http://whois.domaintools.com/<?php echo $aMain['ip']; ?>"><?php echo $aMain['ip']; ?></a>
                            </td>
                            <td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" width="10%">
                              <p style="font: bold 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C;"><?php echo $aligned; ?></p>
                            </td>
                            <td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" colspan="2" width="20%">
                              <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="10" width="14" border="0"></p>
                            </td>
                            <td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" colspan="2" width="20%">
                              <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="10" width="14" border="0"></p>
                            </td>
                          </tr>
						  <?php  
								 }
								 
								 if ($alignedipv6 > 0) 
								 {
						  ?> 
						  <tr>
                            <td style="padding: 0.5em 0 0.5em 8px;background-color: #EFFCF2;border-collapse: collapse;" width="50%">
                              <a style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C; word-break: break-all; text-decoration: none;" class="ip-address" target="_blank" href="http://whois.domaintools.com/<?php echo $aMain['ipv6']; ?>"><?php echo $aMain['ipv6']; ?></a>
                            </td>
                            <td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" width="10%">
                              <p style="font: bold 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C;"><?php echo $alignedipv6; ?></p>
                            </td>
                            <td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" colspan="2" width="20%">
                              <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="10" width="14" border="0"></p>
                            </td>
                            <td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" colspan="2" width="20%">
                              <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="10" width="14" border="0"></p>
                            </td>
                          </tr>
						  
						   <?php  
								 }
						  
							for ($j = 0; $j < count($aNotConfig) ; $j++)
							{
								$record = $aNotConfig[$j];
						  ?>
							  <tr>
								<td style="padding: 0.5em 0 0.5em 8px;background-color: #EFFCF2;border-collapse: collapse;" width="50%">
								  <a style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C; word-break: break-all; text-decoration: none;" class="ip-address" target="_blank" href="http://whois.domaintools.com/<?php IF ($record['source_ip_version']==4) echo $aMain['ip']; else echo $aMain['ipv6']; ?>"><?php IF ($record['source_ip_version']==4) echo $aMain['ip']; else echo $aMain['ipv6']; ?></a>
								</td>
								<td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" width="10%">
								  <p style="font: bold 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C;"><?php echo $record['dkim_pass']+$record['dkim_failed']; ?></p>
								</td>
								<td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" colspan="2" width="20%">
								  <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?><?php if ( $record['spf_pass'] > 0) { echo 'i-check.png'; } else { echo 'i-cross.png' ;} ?>" alt="" height="10" width="14" border="0"></p>
								</td>
								<td style="text-align: center;padding: 0.5em 0;background-color: #EFFCF2;border-collapse: collapse;" colspan="2" width="20%">
								  <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?><?php if ( $record['dkim_pass'] > 0) { echo 'i-check.png'; } else { echo 'i-cross.png' ;} ?>" alt="" height="10" width="14" border="0"></p>
								</td>
							  </tr>
							  
						    <?php 
							}
						  ?>
						 
						  </tbody>
						</table>
                    </div>

                    <!-- Unknown/Threats -->
					<div style="margin-bottom: 40px;" class="pm-content-section pm-content-section--unknown">
                      <h2 style="margin: 0 8px 0.5em; padding: 0; color: #222; font: bold 20px 'Helvetica Neue', Helvetica, Arial, sans-serif;">Unknown/Threats
                        <span style="font: normal 18px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding-left: 5px; color: #E6735C;"><?php echo $failed+$threats; ?></span>
                      </h2>
                      <p style="margin: 0 8px 15px; padding: 0; color: #888; font: normal 13px/21px 'Helvetica Neue', Helvetica, Arial, sans-serif; text-align: justify;" class="pm-section-body">No alignment means that neither DKIM or SPF pass the DMARC policy. These messages are either spam (spoofed) or require your attention for SPF / DKIM authentication. It's important to monitor these emails closely.
                      </p>

                      <table style="margin: 0;" cellspacing="0" cellpadding="0" width="100%" class="pm-dmarc-data">
						<tbody>
						<!-- Headers -->
                          
                          <!-- Results -->
						  <?php 
							for ($i = 0; $i < count($aError) ; $i++)
							{
								$record = $aError[$i];
								
								if ($i == 0)
								{
									$currentDomain = $record['domain'];
									$show = true;
								}
								else
								{
									if ( $currentDomain != $record['domain'] )
									{
										$currentDomain = $record['domain'];
										$show = true;
									}
									else
										$show = false;
								}
								
							if ($show)
							{
						  ?>

							<tr>
							<td>
								<br/><br/>
							</td>
							</tr>
							<tr>
								<th style="text-align: left; font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0 0.8em 8px; border-bottom: 2px solid #e4e4e4;" valign="bottom" width="340px"><a id="trusted-0" style="font: bold 14px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; word-break: break-all; text-align: left; text-decoration: none; color: #222;" href="http://<?php echo $record['domain'] ?>"><?php echo $record['domain'] ?></a></th>
								<th style="text-align: center; font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0; border-bottom: 2px solid #e4e4e4; color: #888;" valign="top" width="68px">Total</th>
								<th style="text-align: center; font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0 0; border-bottom: 2px solid #e4e4e4; color: #888;" colspan="2" width="136px">SPF
								  <table style="text-align: center; padding-top: 5px; font-size: 9px;" cellspacing="0" cellpadding="0" width="136px">
									<tr>
									  <td style="text-align: center; color: #8ED49C; border-right: 1px dotted #e4e4e4; padding: 4px 0;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="8" width="11" border="0" /></td>
									  <td style="text-align: center; color: #E6735C; padding: 4px 0;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-cross.png" alt="" height="8" width="8" border="0" /></td>
									</tr>
								  </table>
								</th>
								<th style="font: normal 11px 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 0.8em 0 0; border-bottom: 2px solid #e4e4e4; color: #888;" colspan="2" width="136px">DKIM
								  <table style="padding-top: 5px; font-size: 9px;" cellspacing="0" cellpadding="0" width="136px">
									<tr>
									  <td style="text-align: center; color: #8ED49C; border-right: 1px dotted #e4e4e4; padding: 4px 0;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-check.png" alt="" height="8" width="11" border="0" /></td>
									  <td style="text-align: center; color: #E6735C; padding: 4px 0;" align="center" width="68px"><img src="<?php echo $pluginPath; ?>i-cross.png" alt="" height="8" width="8" border="0" /></td>
									</tr>
								  </table>
								</th>
							  </tr>
<?php
							}
?>
							  
							  <tr>
								<td style="padding: 0.5em 0 0.5em 8px; background-color: #EFFCF2;" width="50%">
								  <a style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C; word-break: break-all; text-decoration: none;" class="ip-address" target="_blank" href="http://whois.domaintools.com/<?php echo $record['ip'] ?>"><?php echo $record['ip'] ?></a>
								</td>
								<td style="text-align: center; padding: 0.5em 0; background-color: #EFFCF2;" width="10%">
								  <p style="font: bold 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #8ED49C;"><?php echo $record['dkim_pass']+$record['dkim_failed']; ?></p>
								</td>
								<td style="text-align: center; padding: 0.5em 0; background-color: #EFFCF2;" colspan="2" width="20%">
								  <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?><?php if ( $record['spf_pass'] > 0) { echo 'i-check.png'; } else { echo 'i-cross.png' ;} ?>" alt="" height="10" width="14" border="0" /></p>
								</td>
								<td style="text-align: center; padding: 0.5em 0; background-color: #EFFCF2;" colspan="2" width="20%">
								  <p style="font: normal 12px 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; color: #888;"><img src="<?php echo $pluginPath; ?><?php if ( $record['dkim_pass'] > 0) { echo 'i-check.png'; } else { echo 'i-cross.png' ;} ?>" alt="" height="10" width="14" border="0" /></p>
								</td>
							  </tr>
							  
						    <?php 
							}
						  ?>
						</tbody>
					  </table>
                    </div>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
			<td style="padding: 0.5em; ;border-collapse: collapse;">
				<!-- Info -->
              <table style="text-align: center; border-top: 1px solid #CEE3ED; background-color: #EDF7FC;" cellpadding="0" cellspacing="0" width="680px">
                <tr>
                  <td style="padding: 30px">

                    <table style="color: #8D9BA6; text-align: left; font: normal 14px/21px 'Helvetica Neue', Helvetica, Arial, sans-serif;" border="0" cellpadding="0" cellspacing="0" width="680px">
                      <tr>
                        <td valign="middle" class="col-l" width="340px">
							<div style=" border-radius: 3px; border: 1px solid #CEE3ED; padding: 10px 0;">
							<span style="text-align: center; display: block; text-decoration: none; color: #8D9BA6; font-size: 11px;">Plugin written by Jean-Philippe Bayard</span>
                            </div>
                        </td>
                        <td class="col-c" width="20"></td>
                        <td align="center" valign="middle" class="col-r" width="340px">
                          <div style=" border-radius: 3px; border: 1px solid #CEE3ED; padding: 10px 0;">
                            <a style="display: block; text-decoration: none;" href="http://postmarkapp.com/">
                              <img style="display: block; margin: .5em auto;" border="0" alt="Postmark" width="130" src="<?php echo $pluginPath; ?>logo-postmark.png" class="pm-info-logo" />
                              <span style="text-align: center; display: block; text-decoration: none; color: #8D9BA6; font-size: 11px;">Transactional email delivery for web apps</span>
                            </a>
                          </div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

			</td>
		 </tr>
		 <tr>
            <td style="text-align:center;">
				<?php
					$time = microtime();
					$time = explode(' ', $time);
					$time = $time[1] + $time[0];
					$finish = $time;
					$total_time = round(($finish - $start), 4);
					echo '<div style="color: white"><strong>Results generated in '.$total_time.' seconds.</strong></div>';
				?>
            </td>
          </tr>
        </table>

        </td><td>
    </td></tr>
  </table>
</div>