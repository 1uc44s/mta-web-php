{{-- ACP → Marka: site adı, kısa ad, slogan, logo, sekme ikonu — docs/ROUTES.md §6, DESIGN §2. --}}
<x-layouts.admin :title="__('admin.branding.title')">
    <x-admin.header :title="__('admin.branding.title')" :description="__('admin.branding.intro')" />

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <x-admin.card>
            <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <fieldset @disabled(! $canUpdate) class="space-y-6">
                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field name="name" :label="__('admin.branding.name')" required>
                            <x-ui.input name="name" :value="$name" maxlength="60" required />
                        </x-ui.field>
                        <x-ui.field name="short_name" :label="__('admin.branding.short_name')" :hint="__('admin.branding.short_hint')" required>
                            <x-ui.input name="short_name" :value="$shortName" maxlength="30" hint required />
                        </x-ui.field>
                    </div>
                    <x-ui.field name="tagline" :label="__('admin.branding.tagline')">
                        <x-ui.input name="tagline" :value="$tagline" maxlength="120" />
                    </x-ui.field>

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-ui.field name="logo" :label="__('admin.branding.logo')" :hint="__('admin.branding.logo_hint')">
                            <input id="f-logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" aria-describedby="f-logo-hint"
                                   class="block w-full rounded-sm border border-border-input bg-surface-2 px-3 py-2 text-sm text-text file:mr-3 file:rounded-pill file:border-0 file:bg-surface-3 file:px-3 file:py-1.5 file:text-text">
                        </x-ui.field>
                        <x-ui.field name="icon" :label="__('admin.branding.icon')" :hint="__('admin.branding.icon_hint')">
                            <input id="f-icon" type="file" name="icon" accept="image/png,image/jpeg,image/webp" aria-describedby="f-icon-hint"
                                   class="block w-full rounded-sm border border-border-input bg-surface-2 px-3 py-2 text-sm text-text file:mr-3 file:rounded-pill file:border-0 file:bg-surface-3 file:px-3 file:py-1.5 file:text-text">
                        </x-ui.field>
                    </div>

                    @if ($canUpdate)<x-ui.button type="submit" icon="save">{{ __('admin.branding.save') }}</x-ui.button>@endif
                </fieldset>
            </form>
        </x-admin.card>

        <div class="space-y-6">
            <x-admin.card :title="__('admin.branding.preview')">
                <div class="rounded-pill border border-border bg-surface-2 px-4 py-3">
                    <x-brand.logo />
                </div>
                <p class="mt-3 text-sm text-text-muted">{{ $logoUrl ? __('admin.branding.current_logo') : __('admin.branding.default_logo') }}</p>
                @if ($logoUrl && $canUpdate)
                    <form method="POST" action="{{ route('admin.branding.remove', 'logo') }}" class="mt-3">@csrf
                        <x-ui.button type="submit" size="sm" variant="ghost" icon="trash-2">{{ __('admin.branding.remove') }}</x-ui.button>
                    </form>
                @endif
            </x-admin.card>

            <x-admin.card :title="__('admin.branding.icon')">
                <div class="flex items-center gap-4">
                    <img src="{{ $iconUrl ?? asset('favicon.svg') }}" alt="" width="64" height="64" class="size-16 rounded-md bg-surface-2">
                    <div class="flex items-center gap-2 rounded-t-md bg-surface-2 px-3 py-2 text-sm text-text">
                        <img src="{{ $iconUrl ?? asset('favicon.svg') }}" alt="" width="16" height="16" class="size-4">
                        <span class="max-w-[12rem] truncate">{{ $name }}</span>
                    </div>
                </div>
                <p class="mt-3 text-sm text-text-muted">{{ $iconUrl ? __('admin.branding.current_icon') : __('admin.branding.default_icon') }}</p>
                @if ($iconUrl && $canUpdate)
                    <form method="POST" action="{{ route('admin.branding.remove', 'icon') }}" class="mt-3">@csrf
                        <x-ui.button type="submit" size="sm" variant="ghost" icon="trash-2">{{ __('admin.branding.remove') }}</x-ui.button>
                    </form>
                @endif
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
