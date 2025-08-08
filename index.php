<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This is the correct and only place to require the autoloader.
require 'vendor/autoload.php';
require_once 'env.php';
loadEnv();
$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
require_once 'OllamaService.php';
use Ollama\OllamaService;
$ollamaClient = new OllamaService($apiKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = $_POST['nl_query'] ?? '';

    if (empty($query)) {
        die('Missing nl_query in POST');
    }

    //echo "<pre>Query received: " . htmlspecialchars($query) . "</pre>";

    try {
        $response = $ollamaClient->chatCreate($query);
        //echo "<pre>Raw response:\n" . print_r($response, true) . "</pre>";

        $responseText = $response->choices[0]->message->content ?? '';
        //echo "<pre>Response text:\n" . htmlspecialchars($responseText) . "</pre>";

        $filters = [];

        if (preg_match('/min_price:\s*(\d+)/i', $responseText, $matches)) {
            $filters['min_price'] = (int) $matches[1];
        }
        if (preg_match('/max_price:\s*(\d+)/i', $responseText, $matches)) {
            $filters['max_price'] = (int) $matches[1];
        }
        if (preg_match('/title:\s*([a-zA-Z]+)/i', $responseText, $matches)) {
            $filters['title'] = $matches[1];
        }

        //echo "<pre>Extracted filters:\n" . print_r($filters, true) . "</pre>";
    } catch (Exception $e) {
        echo "<pre>Exception:\n" . $e->getMessage() . "</pre>";
    }
} else {
    //echo "<pre>Not a POST request</pre>";
}








$categories = json_decode(file_get_contents('json/cat.json'), true);
$products = json_decode(file_get_contents('json/prod.json'), true);

if (!empty($filters)) {
  $products = array_filter($products, function ($prod) use ($filters) {
    $match = true;

    if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
      $match = $match && $prod['price'] >= (float)$filters['min_price'];
    }

    if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
      $match = $match && $prod['price'] <= (float)$filters['max_price'];
    }

    if (!empty($filters['title'])) {
      $kw = strtolower($filters['title']);
      $match = $match && strpos(strtolower($prod['title']), $kw) !== false;
    }

    return $match;
  });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Catalog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .product-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      text-align: center;
    }
    .product-card img {
      max-width: 100%;
      height: auto;
      margin-bottom: 10px;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">

  <!-- Natural Language Search -->
  <form id="nlSearchForm" class="mb-4" method="POST">
    <div class="input-group">
      <input type="text" name="nl_query" id="nl_query" class="form-control" 
      placeholder="Search products between min and maximum price">
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </form>

  <?php if (!empty($filters)): ?>
    <div class="alert alert-info">
      <strong>AI Filters Applied:</strong>
      <ul>
        <?php foreach ($filters as $k => $v): ?>
          <li><?= htmlspecialchars($k) ?>: <?= htmlspecialchars($v) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <h1 class="mb-4">Product Catalog</h1>

  <!-- Category Dropdown -->
  <div class="mb-4">
    <select id="categorySelect" class="form-select" onchange="filterProducts()">
      <option value="all">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Product Grid -->
  <div class="row" id="productGrid">
    <?php foreach ($products as $prod): ?>
      <div class="col-md-4 mb-4 product-item"
           data-catid="<?= $prod['catid'] ?>"
           data-title="<?= strtolower($prod['title']) ?>"
           data-description="<?= strtolower($prod['description']) ?>"
           data-price="<?= $prod['price'] ?>">
        <div class="product-card bg-white shadow-sm">
          <img src="<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['title']) ?>">
          <h5><?= htmlspecialchars($prod['title']) ?></h5>
          <p><?= htmlspecialchars($prod['description']) ?></p>
          <strong>$<?= number_format($prod['price'], 2) ?></strong><br>
          <small>Demand: <?= number_format($prod['demand'] * 100, 0) ?>%</small><br>
          <small>Rating: <?= number_format($prod['rating'], 1) ?>/5</small>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
  function filterProducts() {
    const selectedCat = document.getElementById('categorySelect').value;
    const items = document.querySelectorAll('.product-item');

    items.forEach(item => {
      const catid = item.getAttribute('data-catid');
      item.style.display = (selectedCat === 'all' || selectedCat === catid) ? 'block' : 'none';
    });
  }

  document.getElementById('nlSearchForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const query = document.getElementById('nl_query').value;
    const grid = document.getElementById('productGrid');

    // Show loading indicator
    grid.innerHTML = `
      <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2">Loading products...</div>
      </div>
    `;

    fetch('index.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'nl_query=' + encodeURIComponent(query)
    })
    .then(res => res.text())
    .then(html => {
      document.open();
      document.write(html);
      document.close();
    });
  });
</script>


</body>
</html>
