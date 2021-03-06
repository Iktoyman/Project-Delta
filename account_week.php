<?php
	$possible_weeks = array();
	$first_week = mysqli_fetch_assoc(mysqli_query($ch_conn, "SELECT WEEK(DATE_FORMAT(NOW(), '%Y-%m-01'), 1) AS first_week"))['first_week'];
	$last_week = mysqli_fetch_assoc(mysqli_query($ch_conn, "SELECT WEEK(DATE_SUB((DATE_FORMAT((DATE_ADD(NOW(), INTERVAL 1 MONTH)), '%Y-%m-01')) , INTERVAL 1 DAY), 1) AS last_week"))['last_week'];

	$a = 0;
	for ($i = $first_week; $i <= $last_week; $i++) {
		$possible_weeks[$a]['week'] = $i;
		$possible_weeks[$a]['mon'] = mysqli_fetch_assoc(mysqli_query($ch_conn, "SELECT STR_TO_DATE(CONCAT(YEAR(NOW()), $i, 'Monday'), '%X%V %W') AS mon"))['mon'];
		$possible_weeks[$a]['sun'] = mysqli_fetch_assoc(mysqli_query($ch_conn, "SELECT STR_TO_DATE(CONCAT(YEAR(NOW()), $i + 1, 'Sunday'), '%X%V %W') AS sun"))['sun'];
		$a++;
	}

	$week = array();
	$week_res = mysqli_query($ch_conn, "SELECT i.item_id, CONCAT(u.first_name, ' ', u.last_name) as name, 
		i.upload_date, 
		i.change_ticket_id, 
		i.change_type, 
		i.description, 
		i.sys_id, 
		i.server, 
		CONCAT(p.first_name, ' ', p.last_name) AS primary_res, 
		i.actions, 
		i.pht_start_datetime, 
		i.pht_end_datetime, 
		i.reference 
		FROM items i, users u, users p, account a 
		WHERE i.uploader_id = u.user_id
		AND i.account_id = a.acct_id 
		AND i.primary_resource = p.user_id 
		AND a.acct_abbrev = '". $a_id ."' 
		AND a.team_id IN (" . implode(', ', $teams) . ")
		AND WEEK(i.pht_start_datetime, 1) = WEEK(NOW(), 1)
		ORDER BY i.pht_start_datetime");
	$i = 0;
	while ($week_row = mysqli_fetch_array($week_res)) {
		$sec_resources_wk = array();
		$secondary_res = mysqli_query($ch_conn, "SELECT CONCAT(u.first_name, ' ', u.last_name) AS name FROM activity_sec_resources asr, users u WHERE asr.user_id = u.user_id AND asr.item_id = " . $week_row['item_id']);
		while ($secondary_row = mysqli_fetch_array($secondary_res)) {
			$sec_resources_wk[] = $secondary_row['name']; 
		}
		$sr_wk = implode('; ', $sec_resources_wk);
		$week[$i]['id'] = $week_row['item_id'];
		$week[$i]['uploader'] = $week_row['name'];
		$week[$i]['upload_date'] = $week_row['upload_date'];
		$week[$i]['chg_id'] = $week_row['change_ticket_id'];
		$week[$i]['chg_type'] = $week_row['change_type'];
		$week[$i]['desc'] = $week_row['description'];
		$week[$i]['sid'] = $week_row['sys_id'];
		$week[$i]['server'] = $week_row['server'];
		$week[$i]['resources'] = $week_row['primary_res'];
		$week[$i]['sec_resources'] = $sr_wk;
		if ($week_row['actions'] = 'Execute Change')
			$add_action = " - " . mysqli_fetch_assoc(mysqli_query($ch_conn, "SELECT a.activity_name FROM activity a, items i WHERE i.activity_id = a.activity_id AND i.item_id = " . $week_row['item_id']))['activity_name'];
		else 
			$add_action = "";
		$week[$i]['actions'] = $week_row['actions'] . $add_action;
		$week[$i]['ph_sd'] = $week_row['pht_start_datetime'];
		$week[$i]['ph_ed'] = $week_row['pht_end_datetime'];
		$week[$i]['reference'] = $week_row['reference'];
		$i++;
	}

?>
<div role="tabpanel" class="account_details_div_tab tab-pane" id="week">
	<div class="account_details_dropdown_div">
		<select name="sort_by_week" id="sort_by_week" onchange="switchWeek()">
			<option value=""> -- Select Week -- </option>
			<?php
				foreach ($possible_weeks as $val) {
					echo "<option value=" . $val['week'] . "> " . date("M d, Y", strtotime($val['mon'])) . " - " . date("M d, Y", strtotime($val['sun'])) . "</option>";
				}
			?>
		</select>
	</div>
	<table class="table table-hover account_details_table">
		<thead>	<tr>
			<td width=5%> UPLOADER </td>
			<td width=5.5%> DATE AND TIME<br>UPLOADED </td>
			<td width=6%> CHANGE TICKET </td>
			<td width=5%> CHANGE TYPE </td>
			<td width=15%> TITLE / DESCRIPTION </td>
			<td width=5%> SID(s) </td>
			<td width=10%> SERVER(s) </td>
			<td width=7.5%> RESOURCE(s) </td>
			<td width=7%> ACTIONS </td>
			<td width=6.5%> SCHED. START<br>DATE AND TIME<br>(PH TIME) </td>
			<td width=6.5%> SCHED. END<br>DATE AND TIME<br>(PH TIME) </td>
			<td width=10%> REFERENCE MAIL </td>
			<td width=5%> NOTES </td>
		</tr> </thead>
		<tbody id="acct_week_tbody">
		<?php
			if (sizeof($week) == 0) {
				echo "<tr>";
				echo "<td colspan=15 style='font-size: 1vw'> There are no change items for this week </td>";
				echo "</tr>";
			}		
			else {
				for ($i = 0; $i < sizeof($week); $i++) {
					echo "<tr>";
					echo "<td width=5%>" . $week[$i]['uploader'] . "</td>";
					echo "<td width=5.5%>" . date("M d, Y <\b\\r> h:i A", strtotime($week[$i]['upload_date'])) . "</td>";
					echo "<td width=6%><a id='show_dets_link' onclick='showDetails(".$week[$i]['id'].")'>" . $week[$i]['chg_id'] . "</a></td>";
					echo "<td width=5%>" . $week[$i]['chg_type'] . "</td>";
					echo "<td width=15%>" . $week[$i]['desc'] . "</td>";
					echo "<td id='word-wrap-td' width=5%>" . $week[$i]['sid'] . "</td>";
					echo "<td width=10%>" . $week[$i]['server'] . "</td>";
					echo "<td width=7.5%>" . $week[$i]['resources'] . "* <br><i>" . $week[$i]['sec_resources'] . "</i></td>";
					echo "<td width=7%>" . $week[$i]['actions'] . "</td>";
					echo "<td width=6.5%>" . date("M d, Y <\b\\r> h:i A", strtotime($week[$i]['ph_sd'])) . "</td>";
					echo "<td width=6.5%>" . date("M d, Y <\b\\r> h:i A", strtotime($week[$i]['ph_ed'])) . "</td>";
					echo "<td width=10%>" . $week[$i]['reference'] . "</td>";
					echo "<td width=5%><a data-toggle='modal' data-target='#show_ticket_notes' onclick='showNotes(".$week[$i]['id'].")'>View Notes</a></td>";
					echo "</tr>";
				}
			}
		?>
		</tbody>
	</table>
</div> 

<script>
	function switchWeek() {
		var week = document.getElementById('sort_by_week').value;
		var a_id = '<?php echo $a_id; ?>';
		var tbody = document.getElementById('acct_week_tbody');
		document.getElementById('cur_week').innerHTML = "";
		tbody.innerHTML = '';
		$.ajax({
			type: "POST",
			url: "acct_queries.php",
			data: {
				'action': week,
				'class_type': 'week',
				'a_id': a_id
			},
			dataType: 'json',
      success: function(response){
          console.log(response);
      },
      error: function(xhr, ajaxOptions, thrownError){
        alert(xhr.status);
        alert(thrownError);
      }
		})
		.done(function(data) {
			console.log('Return is ' + data.length);
			if (data.length == 0) {
				tbody.innerHTML += "<tr><td colspan=15 style='font-size:1vw'>There are no change items for this week</td></tr>"; 
			}
			else {
				for (var x = 0; x < data.length; x++) {
					document.getElementById('acct_week_tbody').innerHTML += "<tr>" + "<td width=5%>" + data[x]['name'] + "</td>" 
					+ "<td width=5.5%>" + data[x]['up_date'] + "</td>"
					+ "<td width=6%><a id='show_dets_link' onclick='showDetails(" + data[x]['id'] + ")'>" + data[x]['chg_id'] + "</a></td>"
					+ "<td width=5%>" + data[x]['chg_type'] + "</td>"
					+ "<td width=15%>" + data[x]['chg_desc'] + "</td>"
					+ "<td id='word-wrap-td' width=5%>" + data[x]['sid'] + "</td>"
					+ "<td width=10%>" + data[x]['server'] + "</td>"
					+ "<td width=7.5%>" + data[x]['assignees'] + "</td>"
					+ "<td width=7%>" + data[x]['action'] + "</td>"
					+ "<td width=6.5%>" + data[x]['ph_sd'] + "</td>"
					+ "<td width=6.5%>" + data[x]['ph_ed'] + "</td>"
					+ "<td width=10%>" + data[x]['ref'] + "</td>"
					+ "<td width=5%><a data-toggle='modal' data-target='#show_ticket_notes' onclick='showNotes(" + data[x]['id'] + ")'>" + data[x]['notes'] + "</a></td>"
					+ "</tr>";
				}
			}
		});
	}
</script>

