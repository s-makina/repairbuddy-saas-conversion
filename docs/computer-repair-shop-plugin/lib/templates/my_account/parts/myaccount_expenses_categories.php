<?php
    defined( 'ABSPATH' ) || exit;

    if ( $role != 'store_manager' && $role != 'administrator' ) {
        echo esc_html__( "You do not have sufficient permissions to access this page.", "computer-repair-shop" );
        exit;
    }

    $expense_manager = WC_CR_EXPENSE_MANAGEMENT();
    $categories      = $expense_manager->get_categories( 'expense', false );
?>
    <!-- Categories Content -->
    <main class="dashboard-content container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"></h4>
            <button type="button" class="btn btn-primary btn-sm" 
                    data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle me-1"></i>
                <?php esc_html_e('Add Category', 'computer-repair-shop'); ?>
            </button>
        </div>

        <!-- Categories Grid -->
        <div class="row g-3">
            <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-tags fs-1 text-muted"></i>
                            <p class="mt-3 text-muted"><?php esc_html_e('No categories found', 'computer-repair-shop'); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ( $categories as $category ): ?>
                    <div class="col-md-4">
                        <div class="card category-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <div class="category-icon rounded-circle d-flex align-items-center justify-content-center" 
                                             style="background-color: <?php echo esc_attr( $category->color_code ); ?>; width: 50px; height: 50px;">
                                            <i class="bi bi-tag text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1"><?php echo esc_html( $category->category_name ); ?></h5>
                                        <?php if ( $category->category_description ): ?>
                                            <p class="card-text text-muted small mb-2">
                                                <?php echo esc_html( $category->category_description ); ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-<?php echo $category->is_active ? esc_attr( 'success' ) : esc_attr( 'secondary' ); ?>">
                                                <?php echo $category->is_active ? esc_html__( 'Active', 'computer-repair-shop' ) : esc_html__( 'Inactive', 'computer-repair-shop' ); ?>
                                            </span>
                                            <?php if ( $category->taxable ): ?>
                                                <span class="badge bg-info">
                                                    <?php echo sprintf( esc_html__( 'Tax: %s%%', 'computer-repair-shop' ), $category->tax_rate ); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu shadow-sm">
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                   data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                   data-category-id="<?php echo esc_attr( $category->category_id ); ?>">
                                                    <i class="bi bi-pencil-square text-primary me-2"></i>
                                                    <?php esc_html_e( 'Edit', 'computer-repair-shop' ); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger delete-category-btn" href="#" 
                                                data-category-id="<?php echo esc_attr( $category->category_id ); ?>">
                                                    <i class="bi bi-trash text-danger me-2"></i>
                                                    <?php esc_html_e( 'Delete', 'computer-repair-shop' ); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php esc_html_e('Add New Category', 'computer-repair-shop'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" data-async method="post">
                        <input type="hidden" name="action" value="wcrb_add_expense_category">
                        <input type="hidden" name="nonce" value="<?php echo esc_html( wp_create_nonce( 'wcrb_expense_category_nonce' ) ); ?>">
                        
                        <div class="mb-3">
                            <label for="category_name" class="form-label">
                                <?php esc_html_e('Category Name *', 'computer-repair-shop'); ?>
                            </label>
                            <input type="text" class="form-control" id="category_name" 
                                   name="category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_description" class="form-label">
                                <?php esc_html_e('Description', 'computer-repair-shop'); ?>
                            </label>
                            <textarea class="form-control" id="category_description" 
                                      name="category_description" rows="2"></textarea>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="color_code" class="form-label">
                                    <?php esc_html_e('Color', 'computer-repair-shop'); ?>
                                </label>
                                <input type="color" class="form-control form-control-color" 
                                       id="color_code" name="color_code" 
                                       value="#3498db" title="Choose color">
                            </div>
                            <div class="col-md-6">
                                <label for="sort_order" class="form-label">
                                    <?php esc_html_e('Sort Order', 'computer-repair-shop'); ?>
                                </label>
                                <input type="number" class="form-control" id="sort_order" 
                                       name="sort_order" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="taxable" name="taxable" value="1" checked>
                                    <label class="form-check-label" for="taxable">
                                        <?php esc_html_e('Taxable', 'computer-repair-shop'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">
                                        <?php esc_html_e('Active', 'computer-repair-shop'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3" id="tax_rate_field" style="display: block;">
                            <label for="tax_rate" class="form-label">
                                <?php esc_html_e('Tax Rate (%)', 'computer-repair-shop'); ?>
                            </label>
                            <input type="number" class="form-control" id="tax_rate" 
                                   name="tax_rate" value="0" step="0.01" min="0" max="100">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php esc_html_e('Close', 'computer-repair-shop'); ?>
                    </button>
                    <button type="button" class="btn btn-primary" id="submitCategoryForm">
                        <?php esc_html_e('Add Category', 'computer-repair-shop'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php esc_html_e('Edit Category', 'computer-repair-shop'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm" data-async method="post">
                        <input type="hidden" name="action" value="wcrb_update_category">
                        <input type="hidden" name="nonce" value="<?php echo esc_html( wp_create_nonce( 'wcrb_expense_category_nonce' ) ); ?>">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">
                                <?php esc_html_e('Category Name *', 'computer-repair-shop'); ?>
                            </label>
                            <input type="text" class="form-control" id="edit_category_name" 
                                name="category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_category_description" class="form-label">
                                <?php esc_html_e('Description', 'computer-repair-shop'); ?>
                            </label>
                            <textarea class="form-control" id="edit_category_description" 
                                    name="category_description" rows="2"></textarea>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_color_code" class="form-label">
                                    <?php esc_html_e('Color', 'computer-repair-shop'); ?>
                                </label>
                                <input type="color" class="form-control form-control-color" 
                                    id="edit_color_code" name="color_code" 
                                    value="#3498db" title="Choose color">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_sort_order" class="form-label">
                                    <?php esc_html_e('Sort Order', 'computer-repair-shop'); ?>
                                </label>
                                <input type="number" class="form-control" id="edit_sort_order" 
                                    name="sort_order" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                        id="edit_taxable" name="taxable" value="1">
                                    <label class="form-check-label" for="edit_taxable">
                                        <?php esc_html_e('Taxable', 'computer-repair-shop'); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                        id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                        <?php esc_html_e('Active', 'computer-repair-shop'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3" id="edit_tax_rate_field" style="display: none;">
                            <label for="edit_tax_rate" class="form-label">
                                <?php esc_html_e('Tax Rate (%)', 'computer-repair-shop'); ?>
                            </label>
                            <input type="number" class="form-control" id="edit_tax_rate" 
                                name="tax_rate" value="0" step="0.01" min="0" max="100">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php esc_html_e('Close', 'computer-repair-shop'); ?>
                    </button>
                    <button type="button" class="btn btn-primary" id="submitEditCategoryForm">
                        <?php esc_html_e('Update Category', 'computer-repair-shop'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>