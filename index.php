
<?php
require_once 'db.php';

$search = trim($_GET['search'] ?? '');

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

if (!empty($search)) {
    $countStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM products
         WHERE name LIKE :search1 OR category LIKE :search2'
    );

    $countStmt->execute([
        ':search1' => '%' . $search . '%',
        ':search2' => '%' . $search . '%',
    ]);
} else {
    $countStmt = $pdo->query('SELECT COUNT(*) FROM products');
}

$totalProducts = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalProducts / $perPage);

$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $perPage;

if (!empty($search)) {
    $stmt = $pdo->prepare(
        'SELECT * FROM products
         WHERE name LIKE :search1 OR category LIKE :search2
         ORDER BY id DESC
         LIMIT :limit OFFSET :offset'
    );

    $stmt->bindValue(':search1', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        'SELECT * FROM products
         ORDER BY id DESC
         LIMIT :limit OFFSET :offset'
    );

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$startProduct = $totalProducts > 0 ? $offset + 1 : 0;
$endProduct = min($offset + $perPage, $totalProducts);

function pageUrl(int $page, string $search): string
{
    $params = ['page' => $page];

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'index.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Product Management</h2>
        <a href="add.php" class="btn btn-primary">+ Add New Product</a>
    </div>

    <form method="GET" action="index.php" class="row g-3 mb-4">
        <div class="col-md-6">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Search by product name or category..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">
                Search
            </button>
        </div>

        <?php if ($search !== ''): ?>
            <div class="col-md-2">
                <a href="index.php" class="btn btn-outline-danger w-100">
                    Reset
                </a>
            </div>
        <?php endif; ?>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <strong>Total Products:</strong>
                    <?= number_format($totalProducts) ?>
                </div>

                <div class="text-muted small">
                    Showing <?= $startProduct ?>–<?= $endProduct ?>
                    of <?= number_format($totalProducts) ?> products
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $index => $product): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>

                                    <td>
                                        Rp <?= number_format((float) $product['price'], 0, ',', '.') ?>
                                    </td>

                                    <td>
                                        <?php if ((int) $product['stock'] > 0): ?>
                                            <span class="text-success fw-bold">
                                                <?= (int) $product['stock'] ?> units
                                            </span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold">
                                                Out of stock
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center text-nowrap">
                                        <a
                                            href="edit.php?id=<?= (int) $product['id'] ?>"
                                            class="btn btn-sm btn-warning"
                                        >Edit</a>

                                        <a
                                            href="delete.php?id=<?= (int) $product['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this product?')"
                                        >Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <?php if ($search !== ''): ?>
                                        No products found matching
                                        "<strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong>".
                                    <?php else: ?>
                                        No products found in the database.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div class="text-muted small">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </div>

                    <nav aria-label="Product pagination">
                        <ul class="pagination mb-0 flex-wrap">

                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="<?= htmlspecialchars(pageUrl(max(1, $page - 1), $search), ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>
                                >Previous</a>
                            </li>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            ?>

                            <?php if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= htmlspecialchars(pageUrl(1, $search), ENT_QUOTES, 'UTF-8') ?>">1</a>
                                </li>

                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a
                                        class="page-link"
                                        href="<?= htmlspecialchars(pageUrl($i, $search), ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $i === $page ? 'aria-current="page"' : '' ?>
                                    ><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>

                                <li class="page-item">
                                    <a class="page-link" href="<?= htmlspecialchars(pageUrl($totalPages, $search), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a
                                    class="page-link"
                                    href="<?= htmlspecialchars(pageUrl(min($totalPages, $page + 1), $search), ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $page >= $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>
                                >Next</a>
                            </li>

                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
