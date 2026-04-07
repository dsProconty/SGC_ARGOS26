<?php

require_once "../../config/database.php";
$action = $_GET['action'];

switch ($action) {
    case 'total_cartera':
        $queryTotGestionesMes = "SELECT count(car_id) as cartera 
                                from cartera";

        $resTotal = mysqli_query($mysqli, $queryTotGestionesMes);

        $row = mysqli_fetch_array($resTotal);

        echo $row['cartera'];
        break;
    case 'total_gestiones':
        $queryTotGestionesMes = "SELECT count(ges_id)as total_gestiones 
                                    from gestion where extract(month from ges_fecha) = extract(month from (select now()))";

        $resTotal = mysqli_query($mysqli, $queryTotGestionesMes);

        $row = mysqli_fetch_array($resTotal);

        echo $row['total_gestiones'];
        break;
    case 'gestiones':
        $arr = array();
        $queryTotGestionesMes = "SELECT count(car_id) as sin_gestiones 
                                        from cartera where car_estado = 'sin_gestion'";

        $resTotal = mysqli_query($mysqli, $queryTotGestionesMes);

        $row = mysqli_fetch_array($resTotal);

        $arr['sin_gestiones'] = $row['sin_gestiones'];

        $queryTotGestionesMes = "SELECT count(ges_id) as exitosas from gestion 
        where extract(month from ges_fecha) = extract(month from (select now())) and ges_respuesta = 'pago'";

        $resTotal = mysqli_query($mysqli, $queryTotGestionesMes);

        $row = mysqli_fetch_array($resTotal);

        $arr['exitosas'] = $row['exitosas'];

        $queryTotGestionesMes = "SELECT count(ges_id) as no_contactado from gestion 
        where extract(month from ges_fecha) = extract(month from (select now())) and ges_respuesta = 'no_contactado'";

        $resTotal = mysqli_query($mysqli, $queryTotGestionesMes);

        $row = mysqli_fetch_array($resTotal);

        $arr['negativas'] = $row['no_contactado'];

        $queryTotGestionesMes = "SELECT count(ges_id) as pendientes from gestion 
        where extract(month from ges_fecha) = extract(month from (select now())) and ges_respuesta = 'compromiso'";

        $resTotal = mysqli_query($mysqli, $queryTotGestionesMes);

        $row = mysqli_fetch_array($resTotal);

        $arr['pendientes'] = $row['pendientes'];

        echo json_encode($arr);
        break;
    case 'recuperado_anual':
        $cartera = $_GET['cartera'];
        $meses = array();
        $query = "SELECT extract(month from p.pag_fecha) as mes, sum(p.pag_monto) as monto_total
        from pago p,gestion g, cartera c where c.car_id = g.car_id and g.pag_id = p.pag_id 
        and c.car_tipo = '$cartera' and extract(year from p.pag_fecha) = extract(year from (select now()))
        group by extract(month from p.pag_fecha)";

        $res = mysqli_query($mysqli,$query);

        for ($i=1; $i <= 12; $i++) { 
            $meses[] = 0;
        }

        while($row = mysqli_fetch_array($res)){
            $meses[$row['mes']-1]= $row['monto_total'];
        }
        echo json_encode($meses);
        break;
    case 'cartera_mes':
        $query = "SELECT c.car_tipo as cartera, sum(c.cli_valor_pagar) as valor_total from cartera c group by c.car_tipo order by c.car_tipo asc";

        $res = mysqli_query($mysqli,$query);

        $arr = array();

        while($row = mysqli_fetch_array($res)){
            $arr[]=$row['valor_total'];
        }
        echo json_encode($arr);
        break;
    case 'cartera_porcentaje':
        $queryCarteras = "SELECT sum(c.cli_valor_pagar) as total_carteras from cartera c";
        $queryPagos = "SELECT sum(p.pag_monto) as total_pagos from pago p";
        
        $resCar = mysqli_query($mysqli,$queryCarteras);
        $resPag = mysqli_query($mysqli,$queryPagos);

        $arr = array();
        $rowCar = mysqli_fetch_array($resCar);
        $rowPag = mysqli_fetch_array($resPag);

        $arr['Cartera'] = $rowCar['total_carteras'];
        $arr['Pagos']=$rowPag['total_pagos'];

        echo json_encode($arr);

        break;
    default:
        # code...
        break;
}
