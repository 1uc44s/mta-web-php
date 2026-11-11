<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('app.name') }}</title>
        <link>{{ route('public.news.index') }}</link>
        <description>{{ __('public.pages.news.intro') }}</description>
        <language>tr-TR</language>
        <atom:link href="{{ route('public.feed') }}" rel="self" type="application/rss+xml"/>
@foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ route('public.news.show', $post->slug) }}</link>
            <guid isPermaLink="false">{{ $post->ulid }}</guid>
            <pubDate>{{ $post->published_at?->toRssString() }}</pubDate>
            <description>{{ $excerpts[$post->id] }}</description>
        </item>
@endforeach
    </channel>
</rss>
