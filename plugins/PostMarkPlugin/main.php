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
?>

<strong>Wikipedia's definition : </strong> <br/><br/>
Domain-based Message Authentication, Reporting and Conformance or DMARC is a method of email authentication, that is a way to mitigate email abuse. It expands on two existing mechanisms, the well-known Sender Policy Framework (SPF) and DomainKeys Identified Mail (DKIM), coordinating their results on the alignment of the domain in the From: header field, which is often visible to end users. It allows specification of policies (the procedures for handling incoming mail based on the combined results) and provides for reporting of actions performed under those policies.<br/><br/>

<strong>Learn more about DMARC :</strong> <br/><br/>
<ul>
<li><a href="https://support.google.com/a/answer/2466580?hl=en">Google FAQ</a></li>
<li><a href="http://dmarc.postmarkapp.com/faq/">Postmark FAQ</a></li>
<li><a href="http://en.wikipedia.org/wiki/DMARC">Wikipedia article</a></li>
</ul>




<?php 
	$source_domain = $_SERVER['SERVER_NAME'];
	$dns = dns_get_record( '_dmarc.'.$source_domain );
	
	$dmarcfound = false;
	if ( count($dns) > 0 )
	{
		foreach ($dns as $key => $value){
		//commandes
			if (strpos ( $value['txt'] , '@dmarc.postmarkapp.com') == 0 )
				$dmarcfound = true;
		}
	}

	else
		$dmarcfound=false;
		
	if ($dmarcfound)
	{
		echo '<strong> You must set your _dmarc.'.$source_domain.' TXT record pointing something like <br/><br/>"v=DMARC1; p=none; pct=100; rua=mailto:YOUR_POSTMARK_ID@dmarc.postmarkapp.com; sp=none; aspf=r;"</strong><br/><br/>';
		echo 'You can setup your dmarc record according to Postmark by registering your domain on <a href="http://dmarc.postmarkapp.com/">Postmark website</a><br/>';
		exit();
	}
	


	$private_key = $plugins['PostMarkPlugin']->postmark_private_key;
	
	if ($private_key == "your_private_key")
	{
		echo '<strong> You must set your private key first </strong><br/>';
		echo 'You can setup your dmarc record according to Postmark by registering your domain on <a href="http://dmarc.postmarkapp.com/">Postmark website</a>';
		exit();
	}
	
?>
	
<br/>
<strong>Pick a period for the analyse :</strong> <br/><br/>



<script>
    $(function() {
       $("#start" ).datepicker({
        yearRange: '2000:2050',
        dateFormat : 'yy-mm-dd'
		});
		
		$("#end" ).datepicker({
        yearRange: '2000:2050',
        dateFormat : 'yy-mm-dd'
		});
    });
	
	
</script>

<script type="text/javascript">
      function handleClick(event)
      {
			$('#data').hide();
			$('#loading-image').show();
			jQuery.ajax({
			url: "<?php echo $plugins['PostMarkPlugin']->pluginroot; ?>postmark.php?from_date="+$('#start').val()+"&to_date="+$('#end').val()+"&private_key=<?php echo $plugins['PostMarkPlugin']->postmark_private_key; ?>"+"&plugin_path=<?php echo $plugins['PostMarkPlugin']->pluginroot; ?>",
			timeout:<?php echo $plugins['PostMarkPlugin']->postmark_timeout * 1000; ?>, 
			success: function(result) {
				html = jQuery(result);

				document.getElementById("data").innerHTML=result;
			},
			complete: function(result) {
				$('#loading-image').hide();
				$('#data').show();
			},
			error: function( objAJAXRequest, strError ) {
			
				if (strError == 'timeout')
				{
					$('#loading-image').hide();
					document.getElementById("data").innerHTML="<strong>Error, loading is too long and is canceled - Check your configuration to update maximum timeout.</strong>";
					$('#data').show();
				}
				else
				{
					$('#loading-image').hide();
					$( "#data" ).text( "Error! Type: " + strError );
					$('#data').show();
				}
			} 
		});
      }
</script>
 
<div class="form">

<table>
	<tr>
		<td>
			<p>From: <input class="datepicker" type="text" size="10" id="start"></p>
		</td>
		<td>
			<p>To: <input class="datepicker" type="text" size="10" id="end" /></p>
		</td>
		<td style="vertical-align: middle;">
			<input name="Submit"  type="submit" value="Show" onClick="JavaScript:handleClick()"/>
		</td>
	</tr>
</table>


 
</div>


<div id="loading-image" style="text-align:center;">
	<img src="<?php echo $plugins['PostMarkPlugin']->pluginroot; ?>gif-loading.gif" />
</div>

<div id="data">
	
</div>

<script>
	var myDate = new Date();
	var currentMonth = myDate.getMonth()+1;
	if (currentMonth < 10) 
	{
		currentMonth = '0' + currentMonth;
	} 
	
	var currentDate = myDate.getDate();
	if (currentDate < 10) 
	{
		currentDate = '0' + currentDate;
	} 
	
	var myDate2 = new Date(myDate.setDate(myDate.getDate() - 7));
	var currentMonthstart = myDate2.getMonth()+1;
	if (currentMonthstart < 10) 
	{
		currentMonthstart = '0' + currentMonthstart;
	} 
	
	var currentDatestart = myDate2.getDate();
	if (currentDatestart < 10) 
	{
		currentDatestart = '0' + currentDatestart;
	} 

	var prettyDate =  myDate.getFullYear() + '-' + currentMonth + '-' + currentDate;
	var prettyDatestart =  myDate2.getFullYear() + '-' + currentMonthstart + '-' + currentDatestart;
	$("#start").val(prettyDatestart);
	$("#end").val(prettyDate);
	
	$('#loading-image').hide();
</script>
	
	
<script>
	$("document").ready(function() {
		handleClick();
});
</script>	











