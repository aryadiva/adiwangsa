<x-mail::message>
# {{ __('mail.daily_report_heading', ['site' => $siteName]) }}

{{ __('mail.daily_report_body', ['project' => $projectName, 'date' => $reportDate]) }}

{{ __('mail.daily_report_attached') }}

<x-mail::button :url="config('app.url')">
{{ config('app.name') }}
</x-mail::button>

{{ __('mail.regards') }},<br>
{{ config('mail.from.name') }}
</x-mail::message>
