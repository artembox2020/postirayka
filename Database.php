<?php

class Database{
	private $mysqli;
	
	public function __construct() {
	
		//Настройки подключения
		///////////////////////////////////////////////////////////
		$host = 'mysql316.1gb.ua'; //Хост базы данных
		$login = 'gbua_senseserve'; // Пользователь MySqli
		$password = 'e61af71040'; // Пароль MySqli
		$db_name = 'gbua_senseserve'; // Имя базы данных
		///////////////////////////////////////////////////////////

		$this->mysqli = new mysqli($host, $login, $password, $db_name);
		$this->mysqli->query("SET NAMES 'utf8'");
	}
 
  public function query($query) {
	return $this->mysqli->query($query);
  }
}
