<?php
	global $wpdb;	
	$table_name = $wpdb->prefix . "pgpcheckout_transactions";
	
	$transactions = $wpdb->get_results( 
		"
		SELECT 
			id,
			id_transaction,
			id_product,
			status,
			time,
			private_data,
			public_data		
		FROM $table_name
		"
	);
	

?>

<table>
	<tr>
		<th>Id</th>
		<th>Transaction</th>
		<th>Product</th>
		<th>Status</th>
		<th>Date</th>
		<th>Private</th>
		<th>Public</th>
	</tr>
	<?php 
	foreach ( $transactions as $transaction ) 
	{
	?>
	<tr>
		<td><?php echo $transaction->id ?></td>
		<td><?php echo $transaction->id_transaction ?></td>
		<td><?php echo $transaction->id_product ?></td>
		<td><?php echo $transaction->status ?></td>
		<td><?php echo $transaction->time ?></td>
		<td><?php echo $transaction->id ?></td>
		<td><?php echo $transaction->id ?></td>
		
	</tr>
	<?php
	}
	
	?>
</table>