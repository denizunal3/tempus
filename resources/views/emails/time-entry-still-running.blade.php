@component('mail::message')
@if(empty($timeEntry->description))
{{ __('Your currently running time entry is now running for more than 8 hours!') }}
@else
{{ __('Your currently running time entry ":description" is now running for more than 8 hours!', ['description' => $timeEntry->description]) }}
@endif

{{ __('If you forgot to stop the Time Tracker you do that in tempus:') }}

@component('mail::button', ['url' => $dashboardUrl])
{{ __('Go to tempus') }}
@endcomponent

@endcomponent
