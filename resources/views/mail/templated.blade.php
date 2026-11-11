{{-- DB şablonundan render edilmiş e-posta gövdesi (docs/CMS.md §6.3). $body: SafeHtml (Markdown → sanitizer). MailMessage::markdown() ile render edilir; girinti kod bloğu sayılacağı için gövde satırı girintisizdir. --}}
<x-mail::message>
{{ $body }}
</x-mail::message>
