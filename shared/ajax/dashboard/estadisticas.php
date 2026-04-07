<?php
date_default_timezone_set('America/Guayaquil');
require_once "../../config/database.php";

$action = $_GET['action'];

switch ($action) {
    case 'consumo_marcas':
        $marca = $_GET['marca'];
        $meses = array();

        $query = "SELECT extract(month from c.con_fecha)as mes,sum(c.con_valor_total) as total_mes 
        from consumo c, local l,marca m where c.loc_id = l.loc_id and l.mar_id = m.mar_id 
        and m.mar_descripcion = '$marca' and extract(year from c.con_fecha) = extract(year from (select now())) group by mes";

        $res = mysqli_query($mysqli,$query);

        for ($i=1; $i <= 12; $i++) { 
            $meses[] = 0;
        }

        while($row = mysqli_fetch_array($res)){
            $meses[$row['mes']-1]= $row['total_mes'];
        }
        echo json_encode($meses);

        break;
    case 'consumo_semanas':
        $mesActual = date('m');
        $anioActual = date('Y');

        $semanas = array();
        

        $fechaInicioMes = strtotime("$anioActual-$mesActual-01");
        $finMes = date('Y-m-t',$fechaInicioMes);

        $Mes1Semana1Ini = date("Y-m-d",$fechaInicioMes);
        $Mes1Semana2IniTmp = strtotime('+7 days',$fechaInicioMes);
        $Mes1Semana2Ini = date("Y-m-d",$Mes1Semana2IniTmp);
        $Mes1Semana3IniTmp = strtotime('+7 days',$Mes1Semana2IniTmp);
        $Mes1Semana3Ini = date("Y-m-d",$Mes1Semana3IniTmp);
        $Mes1Semana4IniTmp = strtotime('+7 days',$Mes1Semana3IniTmp);
        $Mes1Semana4Ini = date("Y-m-d",$Mes1Semana4IniTmp);


        $inicioMesant1 = strtotime('-1 months',$fechaInicioMes);
        $Mes2Semana1Ini = date("Y-m-d",$inicioMesant1);
        $Mes2Semana2IniTmp = strtotime('+7 days',$inicioMesant1);
        $Mes2Semana2Ini = date("Y-m-d",$Mes2Semana2IniTmp);
        $Mes2Semana3IniTmp = strtotime('+7 days',$Mes2Semana2IniTmp);
        $Mes2Semana3Ini = date("Y-m-d",$Mes2Semana3IniTmp);
        $Mes2Semana4IniTmp = strtotime('+7 days',$Mes2Semana3IniTmp);
        $Mes2Semana4Ini = date("Y-m-d",$Mes2Semana4IniTmp);

        


        $inicioMesant2 = strtotime('-2 months',$fechaInicioMes);
        $Mes3Semana1Ini = date("Y-m-d",$inicioMesant2);
        $Mes3Semana2IniTmp = strtotime('+7 days',$inicioMesant2);
        $Mes3Semana2Ini = date("Y-m-d",$Mes3Semana2IniTmp);
        $Mes3Semana3IniTmp = strtotime('+7 days',$Mes3Semana2IniTmp);
        $Mes3Semana3Ini = date("Y-m-d",$Mes3Semana3IniTmp);
        $Mes3Semana4IniTmp = strtotime('+7 days',$Mes3Semana3IniTmp);
        $Mes3Semana4Ini = date("Y-m-d",$Mes3Semana4IniTmp);

        
        $marca = $_GET['marca'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes3Semana1Ini' and c.con_fecha < '$Mes3Semana2Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes3Semana2Ini' and c.con_fecha < '$Mes3Semana3Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes3Semana3Ini' and c.con_fecha < '$Mes3Semana4Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes3Semana4Ini' and c.con_fecha < '$Mes2Semana1Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];



        //Mes 2

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes2Semana1Ini' and c.con_fecha < '$Mes2Semana2Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes2Semana2Ini' and c.con_fecha < '$Mes2Semana3Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes2Semana3Ini' and c.con_fecha < '$Mes2Semana4Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes2Semana4Ini' and c.con_fecha < '$Mes1Semana1Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        //Mes 1

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes1Semana1Ini' and c.con_fecha < '$Mes1Semana2Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes1Semana2Ini' and c.con_fecha < '$Mes1Semana3Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes1Semana3Ini' and c.con_fecha < '$Mes1Semana4Ini'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];

        $query = "SELECT sum(c.con_valor_total)as consumo from consumo c,local l,marca m 
        where c.loc_id = l.loc_id and l.mar_id = m.mar_id and m.mar_descripcion = '$marca' 
        and c.con_fecha >= '$Mes1Semana4Ini' and c.con_fecha <= '$finMes'";

        $res = mysqli_query($mysqli,$query);

        $row = mysqli_fetch_array($res);
        
        $semanas[] = $row['consumo'];


        echo json_encode($semanas);
        break;
    case 'meses':
        $mesActual = date('m');

        $meses = array();

        $months = array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
        
        $j=0;
        $j = $mesActual-1;
        for ($i=0; $i <3 ; $i++) { 
            $meses[] = $months[$j];
        
            if($j<=0){
                $j=11;
            }else{
                $j--;
            }
        }

        echo json_encode($meses);
        break;
    default:
        # code...
        break;
}
?>