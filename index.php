<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css?" />
<title>To Do List</title>
</head>
<body>

<form action="index.php" method="post">


	<script>
		function setDefaultDueDate() {
			var $dtPicker = $('#dtpDueDate');
			$dtPicker.datepicker();
			$dtPicker.datepicker('setDate', new Date());
		}
	</script>

	<h1>My To Do List</h1>
	
	<div id="topSection">	
		<div id="addTaskDiv" class="taskDiv">
			<span id="lblTaskText" class="headerLabel">Task: <span><input id="txtAddTask" type="text" name="txtAddTask" placeholder="Add a new task">
			<span id="lblDueDate" class="headerLabel">Due Date: <span><input id="dtpDueDate" type="datetime-local" name="dtpDueDate" value="setDefaultDueDate()">
			<button id="btnAddTask" class="button" type="submit" name="btnAddTask" >Add Task</button>				
		</div>
	</div>

	<?php
		abstract class eTaskStatus
		{
			const Pending = 0;
			const Started = 1;
			const Completed = 2;
			const Late = 3;			
		}

		$dbHost = "localhost";
		$dbUser = "root";
		$dbPassword = "Sal0149";
		$dbName = "todo_db_PHP";
		$tableName = "todolist";
		$sqlConnection = new mysqli($dbHost, $dbUser, $dbPassword);
			
		if(!isset($_SESSION['initialLoad']))
		{
			$_SESSION['initialLoad'] = 1;		
			$sqlCommand = "CREATE DATABASE IF NOT EXISTS $dbName";
			
			if ($sqlConnection->query($sqlCommand)==FALSE)
			{
				echo "Error: Could not create database ".$dbName;
			} 
			else
			{
				$sqlConnection->select_db($dbName);
				$sqlConnection->query("CREATE TABLE IF NOT EXISTS $tableName ("
										."ID varchar(36) NOT NULL,"
										."TaskText text NOT NULL,"
										."TaskStatus tinyint(3) unsigned NOT NULL,"
										."TaskDueDate datetime NOT NULL,"
										."DateAdded datetime NOT NULL,"
										."PRIMARY KEY (ID)) "
										."ENGINE=InnoDB DEFAULT CHARSET=utf8"
										);
			}
		}		

		function loadMainContents(&$pSqlConnection, $pTableName)
		{
			$result = $pSqlConnection->query("SELECT TaskStatus, COUNT(ID) AS TaskCount FROM $pTableName GROUP BY TaskStatus ORDER BY TaskStatus ASC");
			
			$index = 0;
			$totalTasks=0;
			
			if ($result->num_rows != 0)
			{
				echo ("<br><div class='mainTableDiv'>" 
					."<table id='mainTable'>"
					."<tr>"
					."<th>Task Status</th>"
					."<th>Count</th> "
					."</tr>"
					."<tr>");
					
				while ($result->num_rows != 0 && $row=($result->fetch_assoc()))
				{
					$tmpTaskStatus = "";
					switch ($row[TaskStatus]) {
						case (eTaskStatus::Pending):
							$tmpTaskStatus = "Pending";
							break;
						case (eTaskStatus::Started):
							$tmpTaskStatus = "Started";
							break;
						case (eTaskStatus::Completed):
							$tmpTaskStatus = "Completed";
							break;						
						case (eTaskStatus::Late):
							$tmpTaskStatus = "Late";
							break;	
						default:
							echo "Unknown Status!";
					}				
					
					echo("<td>"	
					."<button id='btnTaskStatus' class='taskStatusButton' type='submit' name='btnTaskStatus' value=$row[TaskStatus]>$tmpTaskStatus</button>"			
					."</td>"			
					."<td>"
					."$row[TaskCount]"
					."</td>"
					."</tr>");
					
					$totalTasks+=$row[TaskCount];										
					++$index;
				}
				
				echo("<tr id='totalRow'>"
					."<td id='totalText'>"	
					."Total"			
					."</td>"			
					."<td id='totalValue'>"
					."$totalTasks"
					."</td>"
					."</tr>"
					."</table></div><br/><hr/>");
			}
			else
			{
				echo ("<label id='tasksMessage'>You Have No Tasks.</label>");
			}					
		}
	
		function loadTasks($pTaskStatus, &$pSqlConnection, $pTableName)
		{			
			$sqlCommand="SELECT * FROM $pTableName";
			
			if ($pTaskStatus!=-1)
			{
				$sqlCommand.=" WHERE TaskStatus=$pTaskStatus";
			}
			
            $result = $pSqlConnection->query($sqlCommand);

            $index = 0;
			
			if ($result->num_rows != 0)
			{
				echo ("<br><div class='mainTableDiv'>" 
					."<table id='mainTable'>"
					."<tr>"
					."<th>Task</th>"
					."<th>Due Date</th>"
					."<th>Status</th> "
					."<th>Date Added</th>"
					."</tr>"
					."<tr>");
				
				while ($result->num_rows != 0 && $row = ( $result->fetch_assoc() ))
				{
					$tmpTaskStatus = "";
					switch ($row[TaskStatus]) {
						case (eTaskStatus::Pending):
							$tmpTaskStatus = "Pending";
							break;
						case (eTaskStatus::Started):
							$tmpTaskStatus = "Started";
							break;
						case (eTaskStatus::Completed):
							$tmpTaskStatus = "Completed";
							break;						
						case (eTaskStatus::Late):
							$tmpTaskStatus = "Late";
							break;	
						default:
							echo "Unknown Value!";
					}				
					
					$dueDate = date('m-d-Y h:i A', strtotime($row[TaskDueDate]));
					$dateAdded = date('m-d-Y h:i A', strtotime($row[DateAdded]));
					
					echo("<td>"	
					."<input type='checkbox' class='taskCheckBox' name='tasksArray[]' value=$row[ID]> $row[TaskText]"			
					."</td>"
					."<td>"
					."$dueDate"
					."</td>"				
					."<td>"
					."$tmpTaskStatus"
					."</td>"
					."<td>"
					."$row[DateAdded]"
					."</td>"				
					."</tr>");
					
					++$index;
				}

				echo ("</table></div>");

				echo ("<br><div id='deleteTaskDiv' class='taskDiv'>"
					."<button id='btnDeleteTask' class='button' type='submit' name='btnDeleteTask' >Remove Task(s)</button>"
					."</div>");	
			}
		}

		loadMainContents($sqlConnection, $tableName);
				
		if (isset($_POST["btnAddTask"])) {
			$taskText = $_POST["txtAddTask"];
			$taskDueDate = $_POST["dtpDueDate"];
			
			if (strlen(trim($taskText)) !=0 && strlen(trim($taskDueDate)) !=0)
			{				
				$sqlConnection->query("INSERT INTO todolist(ID, TaskText, TaskStatus, TaskDueDate, DateAdded) VALUES (UUID(),'$taskText',0,'$taskDueDate',NOW())");
			}		
		} 
		else if(isset($_POST["btnDeleteTask"]))
		{			
			$tasksTodelete = $_POST['tasksArray'];
			
			if (sizeof($tasksTodelete)>0)
			{
				foreach ($_POST['tasksArray'] AS $item)
				{
					$sqlConnection->query("DELETE FROM $tableName WHERE ID = '$item'");
				}				
			}
			else
			{
				echo "No items to delete!";
			}
		}
		else if(isset($_POST["btnTaskStatus"]))
		{	
			$myTaskStatus = $_POST["btnTaskStatus"];
			loadTasks($myTaskStatus , $sqlConnection, $tableName);
		}
		
		$sqlConnection->commit();

		$sqlConnection->close();
	?>
	
</form>

</body>
</html>