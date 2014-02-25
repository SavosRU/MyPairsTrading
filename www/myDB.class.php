<?php

class MyDB {
	private $db = null;
	private $result = null;

	/*������������ �������� �����, ��� ������������, ������, ��� ���� ������, ����, � ����� ��������� ��� ����������.
	�� ��������� ������������ utf8*/

	public function __construct($host, $user, $password, $base, $port = null, $charset = 'utf8') {
		$this->db = new mysqli($host, $user, $password, $base, $port);
		$this->db->set_charset($charset);
	}

	/*�������� � ������������ �������, ������� ��������� ������ � ���������� ��������� ��� ������*/

	public function query($query) {
		if(!$this->db)
		return false;

		/*������� ���������� ���������*/
		if(is_object($this->result))
		$this->result->free();

		/*��������� ������*/
		$this->result = $this->db->query($query);

		/*���� ���� ������ - ������� ��*/
		if($this->db->errno)
			die("mysqli error #".$this->db->errno.": ".$this->db->error);

		/*���� � ���������� ���������� ������� (�������� SELECT...) �������� ������ - ���������� ��.
		��������! ������ ������ ������������ � �������, ���� ���� ������ ���������� ���� ������.*/
		if(is_object($this->result)) {
			$data = NULL;
			while($row = $this->result->fetch_assoc())
				$data[] = $row;

			return $data;
		}
		/*���� ��������� ������������� - ���������� false*/
		else if($this->result == FALSE)
			return false;

		/*���� ������ (�������� UPDATE ��� INSERT) �������� �����-���� ������ - ���������� �� ����������*/
		else return $this->db->affected_rows;
	}
}

?>