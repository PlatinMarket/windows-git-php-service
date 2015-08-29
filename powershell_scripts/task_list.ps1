
<# Get All Windows Tasks #>
Get-ScheduledTask | Select-Object Taskname, State | ConvertTo-Json
exit(0)
