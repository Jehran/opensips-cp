<?php
 /*
 * Copyright (C) 2018 OpenSIPS Project
 *
 * This file is part of opensips-cp, a free Web Control Panel Application for 
 * OpenSIPS SIP server.
 *
 * opensips-cp is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * opensips-cp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
?>

<div id="dialog" class="dialog" style="display:none"></div>
<div onclick="closeDialog();" id="overlay" style="display:none"></div>
<div id="content" style="display:none"></div>

<?php
//fetch cache data

$mi_connectors=get_proxys_by_assoc_id($talk_to_this_assoc_id);

// fetch data from the first box only
$message = mi_command('rtpengine_show',$mi_connectors[0], $errors,$status);

$message = json_decode($message,true);
$message = $message['Set'];
$data_no = count($message);

$rtpengine_cache = array();

// $message is an array of sets right now
for ($i=0; $i<count($message);$i++) {
	// get each node from the SET
	$set = $message[$i]['value'];
	for ($j=0; $j<count($message[$i]['children']['node']); $j++){
		$node = $message[$i]['children']['node'][$j];
		$rtpengine_cache[ $set ][ $node['value'] ]['status'] = $node['attributes']['disabled'];
		$rtpengine_cache[ $set ][ $node['value'] ]['weight'] = $node['attributes']['weight'];
		$rtpengine_cache[ $set ][ $node['value'] ]['ticks']  = $node['attributes']['recheck_ticks'];
		
		if ($node['attributes']['disabled'] == 1){
			$rtpengine_cache[ $set ][ $node['value'] ]['state_link'] 	= '<a href="'.$page_name.'?action=change_state&state='.$node['attributes']['disabled'].'&sock='.$node['value'].'"><img name="status'.$i.'" src="../../../images/share/inactive.png" alt="'.$node['attributes']['disabled'].'" onclick="return confirmStateChange(\''.$node['attributes']['disabled'].'\')" border="0"></a>';
		} else if ($node['attributes']['disabled'] == 0){
			$rtpengine_cache[ $set ][ $node['value'] ]['state_link'] 	= '<a href="'.$page_name.'?action=change_state&state='.$node['attributes']['disabled'].'&sock='.$node['value'].'"><img name="status'.$i.'" src="../../../images/share/active.png" alt="'.$node['attributes']['disabled'].'" onclick="return confirmStateChange(\''.$node['attributes']['disabled'].'\')" border="0"></a>';
		}
	}
} 	

$sql_search="";
$search_setid=$_SESSION['rtpengine_setid'];
$search_sock=$_SESSION['rtpengine_sock'];

if($search_setid!="") { 
	$sql_search.="and set_id = ?";
	$sql_values[] = $search_setid;
}

if ( $search_sock!="" ) {
	$sql_search.=" and socket like ?";
	$sql_values[] = "%".$search_sock."%";
} else {
	$sql_search.=" and socket like '%'";		
}


require("lib/".$page_id.".main.js");

if(!$_SESSION['read_only']){
	$colspan = 8;
}else{
	$colspan = 5;
}
  ?>
<form action="<?=$page_name?>?action=search" method="post">
<table width="350" cellspacing="2" cellpadding="2" border="0">
  <tr>
  <td class="searchRecord">RTPEngine Socket</td>
  <td class="searchRecord" width="200"><input type="text" name="rtpengine_sock" 
  value="<?=$search_sock?>" class="searchInput"></td>
 </tr>
  <tr>
  <td class="searchRecord">Setid</td>
  <td class="searchRecord" width="200"><input type="text" name="rtpengine_setid" 
  value="<?=$search_setid?>" maxlength="16" class="searchInput"></td>
 </tr>
  <tr height="10">
  <td colspan="2" class="searchRecord border-bottom-devider" align="center">
  <input type="submit" name="search" value="Search" class="searchButton">&nbsp;&nbsp;&nbsp;
  <input type="submit" name="show_all" value="Show All" class="searchButton"></td>
 </tr>
</table>
</form>

<?php if (!$_SESSION['read_only']) { ?>
<form action="<?=$page_name?>?action=add" method="post">
  <input type="submit" name="add_new" value="Add RTPEngine" class="formButton"> &nbsp;&nbsp;&nbsp;
  <input onclick="apply_changes()" name="reload" class="formButton" value="Reload on Server" type="button"/>
</form>
<? } ?>

<table class="ttable" width="95%" cellspacing="2" cellpadding="2" border="0">
 <tr align="center">
  <th class="listTitle">ID</th>
  <th class="listTitle">RTPEngine Sock</th>
  <th class="listTitle">Setid</th>
  <th class="listTitle">Weight</th>
  <th class="listTitle">Ticks</th>
  <?
  if(!$_SESSION['read_only']){
  	echo('<th class="listTitle">Memory State</th>');
  	echo('<th class="listTitle">Edit</th>'); 
	echo ('<th class="listTitle">Delete</th>');
  }
  ?>
 </tr>
<?php

$sql_command = "select * from ".$table." where (1=1) ".$sql_search." order by id asc";
$stm = $link->prepare($sql_command);
if ($stm->execute($sql_values) === false)
	die('Failed to issue query, error message : ' . print_r($stm->errorInfo(), true));
$result = $stm->fetchAll(PDO::FETCH_ASSOC);

$data_no = count($result);
if ($data_no == 0)
	echo('<tr><td colspan="'.$colspan.'" class="rowEven" align="center"><br>'.$no_result.'<br><br></td></tr>');
else {

$res_no = $config->results_per_page;
$page = $_SESSION[$current_page];
$page_no = ceil($data_no / $res_no);
if ($page > $page_no) {
	$page = $page_no;
	$_SESSION[$current_page] = $page;
}

$sql_command .= " LIMIT " . $res_no;

$start_limit = ($page - 1) * $res_no;
if ($start_limit != 0)
	$sql_command .= " OFFSET " . $start_limit;

$stm = $link->prepare($sql_command);
if ($stm->execute($sql_values) === false)
	die('Failed to issue query, error message : ' . print_r($stm->errorInfo(), true));
$result = $stm->fetchAll(PDO::FETCH_ASSOC);

require("lib/".$page_id.".main.js");
$index_row=0;
for ($i=0;count($result)>$i;$i++)
{
$index_row++;
if ($index_row%2==1) $row_style="rowOdd";
else $row_style="rowEven";

if(!$_SESSION['read_only']){

	$edit_link = '<a href="'.$page_name.'?action=edit&id='.$result[$i]['id'].'"><img src="../../../images/share/edit.png" border="0"></a>';
	$delete_link='<a href="'.$page_name.'?action=delete&id='.$result[$i]['id'].'"onclick="return confirmDelete()"><img src="../../../images/share/delete.png" border="0"></a>';
}
?>
<tr>
<td class="<?=$row_style?>">&nbsp;<?=$result[$i]['id']?></td>
<td class="<?=$row_style?>">&nbsp;<?=$result[$i]['socket']?></td>
<td class="<?=$row_style?>">&nbsp;<?=$result[$i]['set_id']?></td>
<td class="<?=$row_style?>">&nbsp;<?=isset($rtpengine_cache[$result[$i]['set_id']][$result[$i]['socket']]['weight'])?$rtpengine_cache[$result[$i]['set_id']][$result[$i]['socket']]['weight']:"n/a"?></td>
<td class="<?=$row_style?>">&nbsp;<?=isset($rtpengine_cache[$result[$i]['set_id']][$result[$i]['socket']]['ticks'])?$rtpengine_cache[$result[$i]['set_id']][$result[$i]['socket']]['ticks']:"n/a"?></td>
<? 
if(!$_SESSION['read_only']){
?>
<td class="<?=$row_style?>Img" align="center"><?=isset($rtpengine_cache[$result[$i]['set_id']][$result[$i]['socket']]['state_link'])?$rtpengine_cache[$result[$i]['set_id']][$result[$i]['socket']]['state_link']:"n/a"?></td>
<td class="<?=$row_style?>Img" align="center"><?=$edit_link?></td>
<td class="<?=$row_style?>Img" align="center"><?=$delete_link?></td>
<?php
}
?>  
</tr>  
<?php
}
}
?>
<tr>
<th colspan="<?=$colspan?>">
    <table class="pagingTable">
     <tr>
      <th align="left">Page:
       <?php
       if ($data_no==0) echo('<font class="pageActive">0</font>&nbsp;');
       else {
       	$max_pages = $config->results_page_range;
       	// start page
       	if ($page % $max_pages == 0) $start_page = $page - $max_pages + 1;
       	else $start_page = $page - ($page % $max_pages) + 1;
       	// end page
       	$end_page = $start_page + $max_pages - 1;
       	if ($end_page > $page_no) $end_page = $page_no;
       	// back block
       	if ($start_page!=1) echo('&nbsp;<a href="'.$page_name.'?page='.($start_page-$max_pages).'" class="menuItem"><b>&lt;&lt;</b></a>&nbsp;');
       	// current pages
       	for($i=$start_page;$i<=$end_page;$i++)
       	if ($i==$page) echo('<font class="pageActive">'.$i.'</font>&nbsp;');
       	else echo('<a href="'.$page_name.'?page='.$i.'" class="pageList">'.$i.'</a>&nbsp;');
       	// next block
       	if ($end_page!=$page_no) echo('&nbsp;<a href="'.$page_name.'?page='.($start_page+$max_pages).'" class="menuItem"><b>&gt;&gt;</b></a>&nbsp;');
       }
       ?>
      </th>
      <th align="right">Total Records: <?=$data_no?>&nbsp;</th>
     </tr>
    </table>
  </th>
 </tr>
</table>
<br>


