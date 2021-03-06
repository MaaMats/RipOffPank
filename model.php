<?php

$host = 'localhost';
$user = 'test';
$pass = 't3st3r123';
$db   = 'test';

$l = mysqli_connect($host, $user, $pass, $db);
mysqli_query($l, 'SET CHARACTER SET UTF8');


/**
 * laeb alla andmebaasist tehingud, kus on kasutaja nimi kas
 * maksja all või saaja all ning tagastab selle array'na.
 * @return array
 */
function model_load()
{
    global $l;
    $konto_id = $_SESSION['login'];
    $min      = 0;
    $max      = 20;

    $query = 'SELECT tehingu_id, maksja_id, saaja_id
  , makse_summa FROM mlugus__tehingud WHERE maksja_id=? OR saaja_id=? ORDER BY tehingu_id DESC LIMIT ?, ?';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'iiii', $konto_id, $konto_id, $min, $max);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $maksja, $saaja, $summa);

    $rows = array();
    while (mysqli_stmt_fetch($stmt)) {

        $rows[] = array(
            'id' => $id,
            'maksja' => $maksja,
            'saaja' => $saaja,
            'summa' => $summa
        );
    }

    mysqli_stmt_close($stmt);

    return model_re_table($rows);
}

/**
 * andmebaasist saadud kasutajate id'd muudab kasutajate nimedeks
 * ning tabelis olevad summad paneb vastavalt kas plus või miinus ette.
 * @param $rows
 * @return array $read
 */
function model_re_table($rows)
{

    $konto_id     = $_SESSION['login'];
    $maksja_konto = model_kasutajanimi_get($konto_id);

    $read = array();
    foreach ($rows as $row) {
        $summa = $row['summa'];
        if ($row['maksja'] == $konto_id) {
            $maksja = $maksja_konto;
            $summa  = ((-1) * $row['summa']);
        } else {
            $maksja = model_kasutajanimi_get($row['maksja']);
        }
        if ($row['saaja'] == $konto_id) {
            $saaja = $maksja_konto;
        } else {
            $saaja = model_kasutajanimi_get($row['saaja']);
        }
        $read[] = array(
            'id' => $row['id'],
            'maksja' => $maksja,
            'saaja' => $saaja,
            'summa' => $summa
        );
    }
    return $read;
}

/**
 * otsib andmebaasist id alusel kasutajanime
 * @param $id
 * @return string $kasutajanimi
 */
function model_kasutajanimi_get($id)
{
    global $l;

    $query = 'SELECT kasutajanimi FROM mlugus__kasutajad WHERE id=? LIMIT 1';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $kasutajanimi);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $kasutajanimi;

}

/**
 * Lisab andmebaasi uue kasutaja. Õnnestub vaid juhul kui sellist kasutajat veel pole.
 * Parool salvestatakse BCRYPT räsina.
 *
 * @param string $kasutajanimi Kasutaja nimi
 * @param string $parool       Kasutaja parool
 *
 * @return int lisatud kasutaja ID
 */
function model_user_add($kasutajanimi, $parool)
{
    global $l;
    $kontoj = 2500.00;

    $hash  = password_hash($parool, PASSWORD_DEFAULT);
    $query = 'INSERT INTO mlugus__kasutajad (kasutajanimi, parool, kontoseis) VALUES (?, ?, ?)';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'ssd', $kasutajanimi, $hash, $kontoj);
    mysqli_stmt_execute($stmt);

    $id = mysqli_stmt_insert_id($stmt);

    mysqli_stmt_close($stmt);

    return $id;
}

/**
 * Tagastab kasutaja ID, kelle kasutajanimi ja parool klapivad sisendiga.
 *
 * @param string $kasutajanimi Otsitava kasutaja kasutajanimi
 * @param string $parool       Otsitava kasutaja parool
 *
 * @return int Kasutaja ID
 */
function model_user_get($kasutajanimi, $parool)
{
    global $l;

    $query = 'SELECT id, parool FROM mlugus__kasutajad WHERE kasutajanimi=? LIMIT 1';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 's', $kasutajanimi);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $hash);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // kontrollime, kas vabateksti $parool klapib baasis olnud räsiga $hash
    if (password_verify($parool, $hash)) {
        return $id;
    }

    return false;
}

/**
 *Funktsioon tagastab kasutajanimele vastava id
 * @param $kasutajanimi
 * @return $id
 */
function model_user_id($kasutajanimi)
{
    global $l;

    $query = 'SELECT id FROM mlugus__kasutajad WHERE kasutajanimi=? LIMIT 1';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 's', $kasutajanimi);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $id;

}

/**
 * Funktsioon tagastab id'le vastava kontoseisu
 * @param $id
 * @return double
 */
function model_user_kontoseis($id)
{
    global $l;

    $query = 'SELECT kontoseis FROM mlugus__kasutajad WHERE id=? LIMIT 1';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $kontoseis);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $kontoseis;

}

/**
 * Funktsioon uuendab kahe tehingus osalenute kontoseisu
 * @param $maksja_id
 * @param $saaja_id
 * @param $summa
 * @return bool
 */
function model_kontoseisu_uuendus($maksja_id, $saaja_id, $summa)
{

    global $l;

    $maksja_kontoseis = (model_user_kontoseis($maksja_id) - $summa);
    $saaja_kontoseis  = (model_user_kontoseis($saaja_id) + $summa);

    $query = 'UPDATE mlugus__kasutajad SET kontoseis=? WHERE id=? LIMIT 1';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'di', $maksja_kontoseis, $maksja_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_error($stmt)) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'di', $saaja_kontoseis, $saaja_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_error($stmt)) {
        return false;
    }
    mysqli_stmt_close($stmt);
}

/**
 * Funktsioon sooritab makse, lisades selle andmebaasi
 * @param $saaja
 * @param $summa
 * @return int
 */
function model_makse($saaja, $summa)
{
    global $l;
    $maksja_id = $_SESSION['login'];
    $saaja_id  = model_user_id($saaja);

    $query = 'INSERT INTO mlugus__tehingud (maksja_id, saaja_id, makse_summa) VALUES (?, ?, ?)';
    $stmt  = mysqli_prepare($l, $query);
    if (mysqli_error($l)) {
        echo mysqli_error($l);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'iid', $maksja_id, $saaja_id, $summa);
    mysqli_stmt_execute($stmt);

    $id = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);

    model_kontoseisu_uuendus($maksja_id, $saaja_id, $summa);

    return $id;
}