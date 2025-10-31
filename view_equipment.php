<?php include 'db_connect.php';
$id = (int)$_GET['id'];
$qry = $conn->query("SELECT e.*, s.empresa FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id WHERE e.id = $id");
$eq = $qry->fetch_assoc(); ?>
<div class="container py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><?php echo $eq['name']; ?></h4>
        </div>
        <div class="card-body text-center">
            <p><strong>Inv:</strong> <?php echo $eq['number_inventory']; ?></p>
            <p><strong>Serie:</strong> <?php echo $eq['serie']; ?></p>
            <img src="generate_qr.php?id=<?php echo $id; ?>" class="img-fluid" style="max-width: 250px;">
        </div>
    </div>
</div>