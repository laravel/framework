<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<style>
@media (prefers-color-scheme: dark) {
body,
.wrapper,
.body {
background-color: #18181b !important;
}

.inner-body {
background-color: #27272a !important;
border-color: #3f3f46 !important;
}

p,
ul,
ol,
blockquote,
span,
td {
color: #e4e4e7 !important;
}

a {
color: #a5b4fc !important;
}

h1,
h2,
h3,
.header a {
color: #fafafa !important;
}

.logo {
    filter: invert(23%) sepia(5%) saturate(531%) hue-rotate(202deg) brightness(96%) contrast(91%) !important;
}

.button-primary,
.button-blue {
background-color: #fafafa !important;
border-color: #fafafa !important;
color: #18181b !important;
}

.button-success,
.button-green {
background-color: #22c55e !important;
border-color: #22c55e !important;
color: #fff !important;
}

.button-error,
.button-red {
background-color: #ef4444 !important;
border-color: #ef4444 !important;
color: #fff !important;
}

.footer p,
.footer a {
color: #71717a !important;
}

.panel-content {
background-color: #3f3f46 !important;
}

.panel-content p {
color: #e4e4e7 !important;
}

.panel {
border-color: #fff !important;
}

.subcopy {
border-top-color: #3f3f46 !important;
}

.table th {
border-bottom-color: #3f3f46 !important;
}
}

@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
