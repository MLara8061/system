<?php
// Partial de Navbar superior (sin duplicar <html>, <head> ni scripts)
?>

<?php
$is_admin = (int)($_SESSION['login_type'] ?? 0) === 1;
$active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

$branches = [];
$branch_name = '';

if (isset($conn) && $conn instanceof mysqli) {
  $has_active = false;
  $col = @$conn->query("SHOW COLUMNS FROM branches LIKE 'active'");
  if ($col && $col->num_rows > 0) {
    $has_active = true;
  }

  $branches_sql = "SELECT id, name" . ($has_active ? ", active" : "") . " FROM branches ORDER BY name ASC";
  $res = @$conn->query($branches_sql);
  if ($res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) {
      $active = $has_active ? (int)($row['active'] ?? 1) : 1;
      if ($active !== 1) continue;
      $bid = (int)($row['id'] ?? 0);
      $bname = (string)($row['name'] ?? '');
      if ($bid <= 0 || $bname === '') continue;
      $branches[] = ['id' => $bid, 'name' => $bname];
      if ($active_bid > 0 && $bid === $active_bid) {
        $branch_name = $bname;
      }
    }
    $res->free();
  }
}

if ($branch_name === '' && $active_bid > 0) {
  foreach ($branches as $b) {
    if ((int)$b['id'] === $active_bid) {
      $branch_name = (string)$b['name'];
      break;
    }
  }
}
?>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button">
        <i class="fas fa-bars"></i>
      </a>
    </li>
  </ul>

  <!-- Right navbar links -->
  <ul class="navbar-nav ml-auto user-menu">
    <!-- Selector de Sucursal (global) -->
    <li class="nav-item d-flex align-items-center mr-2">
      <span class="text-muted small mr-2 font-weight-bold d-none d-lg-inline">Sucursal:</span>
      <?php if ($is_admin): ?>
        <select id="global_branch_selector" class="form-control form-control-sm" style="width: 220px;">
          <option value="0" <?= $active_bid === 0 ? 'selected' : '' ?>>Todas</option>
          <?php foreach ($branches as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $active_bid === (int)$b['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <select class="form-control form-control-sm" style="width: 220px;" disabled>
          <option selected><?= htmlspecialchars($branch_name !== '' ? $branch_name : 'Sucursal asignada') ?></option>
        </select>
      <?php endif; ?>
    </li>

    <!-- Search -->
    <li class="nav-item">
      <a class="nav-link" data-widget="navbar-search" href="#" role="button">
        <i class="fas fa-search"></i>
      </a>
    </li>

    <!-- Notifications (E2.2 - dinámicas) -->
    <li class="nav-item dropdown" id="notification-dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-bell"></i>
        <span class="badge badge-warning navbar-badge" id="notif-count" style="display:none">0</span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="max-height:400px;overflow-y:auto;">
        <span class="dropdown-item dropdown-header" id="notif-header">Notificaciones</span>
        <div class="dropdown-divider"></div>
        <div id="notif-list">
          <div class="dropdown-item text-center text-muted small py-3">Sin notificaciones</div>
        </div>
        <div class="dropdown-divider"></div>
        <a href="javascript:void(0)" class="dropdown-item dropdown-footer" id="notif-mark-all">Marcar todas como leídas</a>
      </div>
    </li>

    <!-- Fullscreen -->
    <li class="nav-item">
      <a class="nav-link" data-widget="fullscreen" href="#" role="button">
        <i class="fas fa-expand-arrows-alt"></i>
      </a>
    </li>

    <!-- User Dropdown -->
    <li class="nav-item dropdown">
      <a href="#" class="nav-link dropdown-toggle d-flex align-items-center p-0 px-3" data-toggle="dropdown">
        <img src="assets/avatars/<?= $_SESSION['login_avatar'] ?? 'default-avatar.png' ?>"
             class="img-circle elevation-2 mr-2" alt="Avatar"
             style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #e2e8f0;">
        <span class="d-none d-md-inline font-weight-medium text-gray-700">
          <?= ucwords($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']) ?>
        </span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0"
           style="width: 280px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: visible; z-index: 1055;">
        <div class="px-4 py-3 text-center bg-white" style="border-bottom: 1px solid #e2e8f0;">
          <img src="assets/avatars/<?= $_SESSION['login_avatar'] ?? 'default-avatar.png' ?>"
               class="img-circle elevation-2 mb-2" alt="Avatar"
               style="width: 70px; height: 70px; object-fit: cover; border: 3px solid #f1f5f9;">
          <h6 class="mb-1 font-weight-bold text-gray-900">
            <?= ucwords($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']) ?>
          </h6>
          <p class="mb-1 text-gray-600 small">
            <i class="fas fa-user-tag mr-1"></i>
            <?= ($_SESSION['login_type'] ?? 2) == 1 ? 'Administrador' : 'Usuario' ?>
          </p>
          <p class="text-gray-500 text-xs">
            <i class="far fa-calendar-alt mr-1"></i>
            Miembro desde <?= date('d/m/Y', $_SESSION['login_date_created'] ?? time()) ?>
          </p>
        </div>
        <div class="p-2 bg-light d-flex justify-content-center gap-1">
          <a href="index.php?page=profile" class="btn btn-outline-primary btn-xs px-3 py-1 d-flex align-items-center"
             style="font-size: 0.75rem; border-radius: 6px;">
            <i class="fas fa-user-cog mr-1" style="font-size: 0.8rem;"></i> Mi Perfil
          </a>
          <a href="./public/ajax/logout.php" class="btn btn-outline-primary btn-xs px-3 py-1 d-flex align-items-center"
             style="font-size: 0.75rem; border-radius: 6px;">
            <i class="fas fa-sign-out-alt mr-1" style="font-size: 0.8rem;"></i> Cerrar
          </a>
        </div>
      </div>
    </li>
  </ul>
</nav>

<style>
  .user-menu .dropdown-menu { z-index: 1051 !important; box-shadow: 0 10px 25px rgba(0,0,0,.15) !important; border: 1px solid #e2e8f0 !important; }
  .user-menu .dropdown-menu::before { content: none !important; }
  .user-menu .nav-link { padding: .5rem 1rem !important; transition: background .2s ease; }
  .user-menu .nav-link:hover { background-color: rgba(0,0,0,.05); border-radius: 8px; }
</style>



<?php if ($is_admin): ?>
<script>
  $(function () {
    var $sel = $('#global_branch_selector');
    if (!$sel.length) return;

    $sel.on('change', function () {
      var branch_id = $(this).val();
      if (branch_id === null || branch_id === undefined) return;

      $.ajax({
        url: 'public/ajax/action.php?action=update_user_branch',
        method: 'POST',
        data: { branch_id: branch_id },
        dataType: 'json',
        success: function (data) {
          if (data && data.success) {
            if (typeof alert_toast === 'function') {
              alert_toast('Sucursal cambiada correctamente', 'success');
              setTimeout(function(){ location.reload(); }, 300);
            } else {
              location.reload();
            }
          } else {
            if (typeof alert_toast === 'function') alert_toast('Error al cambiar sucursal', 'error');
          }
        },
        error: function () {
          if (typeof alert_toast === 'function') alert_toast('Error de conexión', 'error');
        }
      });
    });
  });
</script>
<?php endif; ?>

<!-- E2.2: Notificaciones in-app -->
<script>
$(function() {
  function loadNotifications() {
    $.getJSON('public/ajax/action.php?action=get_notifications', function(resp) {
      if (!resp || resp.status !== 1) return;
      var count = resp.count || 0;
      var $badge = $('#notif-count');
      var $list = $('#notif-list');
      var $header = $('#notif-header');

      if (count > 0) {
        $badge.text(count).show();
        $header.text(count + ' Notificaciones');
      } else {
        $badge.hide();
        $header.text('Notificaciones');
      }

      $list.empty();
      if (!resp.notifications || resp.notifications.length === 0) {
        $list.html('<div class="dropdown-item text-center text-muted small py-3">Sin notificaciones</div>');
        return;
      }

      resp.notifications.forEach(function(n) {
        var icon = 'fas fa-bell';
        var iconColor = 'text-primary';
        if (n.type === 'ticket_status') { icon = 'fas fa-exchange-alt'; iconColor = 'text-info'; }
        else if (n.type === 'ticket_comment') { icon = 'fas fa-comment'; iconColor = 'text-success'; }

        var ago = timeAgo(n.created_at);
        var html = '<a href="' + (n.link || '#') + '" class="dropdown-item notif-item" data-id="' + n.id + '">';
        html += '<i class="' + icon + ' ' + iconColor + ' mr-2"></i> ';
        html += '<span class="small">' + (n.message || n.title) + '</span>';
        html += '<br><span class="text-muted" style="font-size:0.7rem;margin-left:1.5rem"><i class="far fa-clock mr-1"></i>' + ago + '</span>';
        html += '</a><div class="dropdown-divider"></div>';
        $list.append(html);
      });
    });
  }

  // Marcar individual al hacer clic
  $(document).on('click', '.notif-item', function() {
    var nid = $(this).data('id');
    if (nid) $.post('public/ajax/action.php?action=mark_notification_read', { id: nid });
  });

  // Marcar todas
  $('#notif-mark-all').on('click', function(e) {
    e.preventDefault();
    $.post('public/ajax/action.php?action=mark_all_notifications_read', function() {
      loadNotifications();
    });
  });

  function timeAgo(dateStr) {
    var now = new Date();
    var date = new Date(dateStr.replace(' ', 'T'));
    var diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'Hace un momento';
    if (diff < 3600) return 'Hace ' + Math.floor(diff/60) + ' min';
    if (diff < 86400) return 'Hace ' + Math.floor(diff/3600) + ' h';
    return 'Hace ' + Math.floor(diff/86400) + ' d';
  }

  // Cargar al iniciar y cada 30s
  loadNotifications();
  setInterval(loadNotifications, 30000);
});
</script>

