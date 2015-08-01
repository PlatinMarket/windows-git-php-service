Param([string]$taskName = "")

<# Validation #>
if ($taskName -eq "") {
  Write-Host "taskName required!"
  exit(1)
}

if (($task = Get-ScheduledTask -TaskName $taskName) -eq $null) {
  Write-Host "Task not found"
  exit(2)
}

if ($task.State -ne "ready") {
  Write-Host "Task already running"
  exit(3)
}

<# Start Task #>
try {
  Start-ScheduledTask -TaskName $taskName | Out-Null
  Write-Host "Task Started"
  exit(0)
} catch {
  Write-Host $_.Exception.Message
  exit(3)
}
