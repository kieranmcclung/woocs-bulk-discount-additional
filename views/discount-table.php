<h3 class="discount-table-header"><?= $view_data['title']; ?></h3>
<table class="discount-table">
	<thead>
		<tr>
			<?php foreach ( $view_data['discounts']['table_header'] as $quantity ) : ?>
				<th><?= $quantity; ?></th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<tr>
			<?php foreach ( $view_data['discounts']['table_body'] as $discount ) : ?>
				<td><?= $discount; ?></td>
			<?php endforeach; ?>	
		</tr>
	</tbody>
</table>