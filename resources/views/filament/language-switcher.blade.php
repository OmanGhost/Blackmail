<form method="post" action="{{ route('locale') }}" class="fi-language-switcher flex items-center gap-2 px-2">
    @csrf
    <label for="admin-locale" class="sr-only">{{ __('admin.language') }}</label>
    <select id="admin-locale" name="locale" onchange="this.form.submit()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-700 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
        <option value="ar" @selected(app()->getLocale()==='ar')>العربية</option>
        <option value="en" @selected(app()->getLocale()==='en')>English</option>
    </select>
</form>
