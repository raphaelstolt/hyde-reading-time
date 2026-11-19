<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Hyde reading-time fixture' }}</title>
</head>
<body>
    <aside data-reading-time>@readingTime</aside>
    @php($readingTime = app(\HydeReadingTime\ReadingTimeService::class)->forPage($page ?? null))
    @if ($readingTime !== null)
        <span data-reading-time-words>{{ $readingTime->wordCount }}</span>
    @endif
    <main id="content">@include('hyde::components.docs.documentation-article')</main>
</body>
</html>