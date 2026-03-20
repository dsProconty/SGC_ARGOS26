<div class="content" data-layout="tabbed">
    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="mr-auto">
                    <h1 class="separator"><?php echo strtoupper($_GET['module']); ?></h1>
                    <nav class="breadcrumb-wrapper" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?module=dashboard"><i class="icon dripicons-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Usuarios</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a class="btn btn-info" href="?module=formulario&action=new" data-toggle="tooltip" style="color:white;">Agregar Usuario</a>
                </div>
            </div>

        </div>
    </header>
    <section class="page-content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">Lista de Usuarios</h5>
                    <div class="table-responsive">
                        <div class="card-body" id="loader_usuarios">



                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script src="js\users.js"></script>