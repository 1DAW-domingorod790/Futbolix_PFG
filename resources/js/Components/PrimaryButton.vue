<!-- <template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-futbolix-green px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-futbolix-green-dark focus:outline-none focus:ring-2 focus:ring-futbolix-green focus:ring-offset-2 focus:ring-offset-futbolix-dark active:bg-futbolix-green-dark disabled:opacity-50"
    >
        <slot />
    </button>
</template> -->



<template>
  <div class="flex justify-center items-center gap-12 h-full">
    <div
      class="bg-gradient-to-b from-gray-800/40 to-transparent p-[4px] rounded-[16px]"
    >
      <button
        class="text-xs font-semibold uppercase tracking-widest text-white group p-[4px] rounded-[12px] bg-gradient-to-b bg-gradient-to-b from-green-500 to-green-700 shadow-[0_2px_4px_rgba(0,0,0,0.7)] hover:shadow-[0_4px_12px_rgba(22,163,74,0.6)] active:shadow-[0_0px_1px_rgba(0,0,0,0.8)] active:scale-[0.995] transition-all duration-200"
      >
        <div
          class="bg-gradient-to-b from-green-700/80 to-green-800/70 rounded-[8px] px-3 py-2"
        >
          <div class="flex gap-2 items-center">
            <span class="font-semibold text-white"><slot /></span>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>
