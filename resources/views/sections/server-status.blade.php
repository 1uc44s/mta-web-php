{{-- CMS §4.2 server_status: veri köprüden gelir, editör sayı giremez. --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
    <div class="grid items-center gap-10 lg:grid-cols-2">
        <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'Sunucu'" :title="$title ?? 'Şehir şu anda ne durumda?'" :intro="$intro ?? null" />
        <x-brand.server-card :status="$status" />
    </div>
</section>
