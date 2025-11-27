<?php

require_once 'format_minimal.php'; // Подключаем фасадную функцию

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ExpertAI</title>
..<link rel="stylesheet" href="assets/style.css?v=fix2">
</head>
<body>
<?php if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])): ?>
<?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); 
  $msgs = isset($flash['messages']) && is_array($flash['messages']) ? $flash['messages'] : [];
  $status = $flash['status'] ?? 'info';
  $statusStyle = ($status==='error') ? 'background:#ffecec;border:1px solid #f5c2c2;color:#b10000;' : 'background:#eef7ff;border:1px solid #b6daf7;color:#084b8a;';
?>
  <div class="flash" style="margin:10px 0;padding:10px;border-radius:8px;<?= $statusStyle ?>">
    <?php if (!empty($msgs)): foreach ($msgs as $m): ?>
      <div><?= htmlspecialchars((string)$m, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endforeach; else: ?>
      <div><?= htmlspecialchars((string)($flash['code'] ?? 'info'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

<div id="container">

  <aside id="sidebar">
    <form action="index.php" method="get">
      <button type="submit" name="action" value="new_chat">➕ New Chat</button>
    </form>

    <ul class="chat-list">
      <?php foreach ($chatList as $c): ?>
        <li class="<?= ($c['title'] === $activeId) ? 'active' : '' ?>">
          <a href="index.php?chat_id=<?= htmlspecialchars($c['title']) ?>">
            <?= htmlspecialchars($c['title']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <form action="index.php" method="get">
      <input type="hidden" name="action" value="export_chat">
      <?php if (!empty($activeId)): ?>
        <input type="hidden" name="chat_id" value="<?= htmlspecialchars($activeId) ?>">
      <?php endif; ?>
      <button type="submit" class="export">📄 Export chat</button>
    </form>

    <form action="index.php" method="get">
      <button class="logout" type="submit" name="action" value="logout">Logout</button>
    </form>

    <div class="user-tag">User: <strong><?= htmlspecialchars($user) ?></strong></div>
  </aside>

  <main id="main">
    <div id="main-header">
      <h1>AI Visimix Expert</h1>

      <?php if (!empty($global_error)): ?>
        <div class="error" style="margin-right:12px;"><?= htmlspecialchars($global_error) ?></div>
      <?php endif; ?>

      <?php if ($activeId): ?>
        <?php if (!empty($rename_error)): ?>
          <div class="error" style="margin-right:12px;"><?= htmlspecialchars($rename_error) ?></div>
        <?php endif; ?>
        <form action="index.php" method="post" class="inline">
          <input type="hidden" name="action" value="rename_chat">
          <input type="hidden" name="chat_id" value="<?= htmlspecialchars($activeId) ?>">
          <input type="text" name="new_title"
                 value="<?= htmlspecialchars($activeId) ?>"
                 class="title-input" required>
          <button type="submit">Rename</button>
        </form>

        <form action="index.php" method="post" class="inline">
          <input type="hidden" name="action" value="delete_chat">
          <input type="hidden" name="chat_id" value="<?= htmlspecialchars($activeId) ?>">
          <button type="submit">Delete chat</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if (isset($confirm_delete) && is_array($confirm_delete)): ?>
      <div class="confirm-box">
        <h3>Confirm deletion</h3>
        <p>Are you sure you want to delete chat “<strong><?= htmlspecialchars($confirm_delete['title']) ?></strong>”?</p>
        <form method="post" action="index.php" class="inline">
          <input type="hidden" name="action" value="delete_chat">
          <input type="hidden" name="chat_id" value="<?= htmlspecialchars($confirm_delete['chat_id']) ?>">
          <input type="hidden" name="confirm" value="1">
          <button type="submit">Yes, delete</button>
          <a class="btn-cancel" href="index.php<?= $activeId ? ('?chat_id=' . urlencode($activeId)) : '' ?>">Cancel</a>
        </form>
      </div>
    <?php endif; ?>

    <?php if (!$activeId): ?>
      <p>No chats yet. Create a new one.</p>
    <?php else: ?>
      <div class="chat-history">
        <?php foreach ($activeBlocks as $b): ?>
          <div class="message user">
            <div class="msg-time"><?= htmlspecialchars($b['Time'] ?? '') ?></div>
            <?= htmlspecialchars($b['Question'] ?? '') ?>
          </div>
          <div class="message ai">
		<?= format_minimal((string)($b['Answer'] ?? '')) ?>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="post" action="index.php" id="composer-form">
        <input type="hidden" name="action" value="ask">
        <input type="hidden" name="chat_id" value="<?= htmlspecialchars($activeId) ?>">
		<textarea name="question" id="question" rows="3" placeholder="Enter your question..." required></textarea>
        <button type="submit">Ask</button>
      </form>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
