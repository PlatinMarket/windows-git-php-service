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

<# Stop Task #>
try {
  Stop-ScheduledTask -TaskName $taskName | Out-Null
  Write-Host $task.State.value__
  exit(0)
} catch {
  Write-Host $_.Exception.Message
  exit(3)
}
