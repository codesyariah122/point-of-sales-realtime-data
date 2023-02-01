<template>
    <header class="pb-3 mb-4 border-bottom">
        <a
            href="/"
            class="d-flex align-items-center text-dark text-decoration-none"
        >
            <img :src="`${asset_url}/img/codot-logo.png`" alt="" width="50" />
            <span class="fs-4 ml-5">Codot Admin</span>
        </a>
    </header>
</template>

<script>
export default {
    data() {
        return {
            asset_url: process.env.MIX_ASSET_URL,
        };
    },
};
</script>
